<?php

namespace Tests\Feature;

use App\Actions\ReviewVenueReservation;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Throwable;

class MySqlVenueReservationConcurrencyTest extends TestCase
{
    public function test_simultaneous_conflicting_approvals_cannot_both_succeed(): void
    {
        if (env('MYSQL_CONCURRENCY_TEST') !== '1' || DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Set MYSQL_CONCURRENCY_TEST=1 and configure a dedicated MySQL test database to run this integration test.');
        }

        if (! extension_loaded('pcntl')) {
            $this->markTestSkipped('The pcntl extension is required for the concurrent MySQL integration test.');
        }

        $database = config('database.connections.'.config('database.default').'.database');

        if (! is_string($database) || ! str_ends_with($database, '_test')) {
            $this->markTestSkipped('The MySQL concurrency test requires a database name ending in _test.');
        }

        Artisan::call('migrate');
        $this->assertTrue(Schema::hasTable('reservations'));

        $organizer = User::factory()->create(['role' => 'organizer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $firstEvent = $this->createEvent($organizer, 'Concurrency Test A');
        $secondEvent = $this->createEvent($organizer, 'Concurrency Test B');
        $venue = Venue::query()->create([
            'name' => 'Concurrency Test Venue',
            'location' => 'Test Campus',
            'capacity' => 20,
            'availability_status' => 'available',
        ]);
        $first = $this->createReservation($firstEvent, $venue, $organizer, '10:00:00', '11:00:00');
        $second = $this->createReservation($secondEvent, $venue, $organizer, '10:30:00', '11:30:00');

        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $this->assertIsArray($sockets);
        $defaultConnection = config('database.default');
        $childPid = pcntl_fork();
        $this->assertNotSame(-1, $childPid);

        if ($childPid === 0) {
            fclose($sockets[0]);
            $connectionName = 'mysql_concurrency_test';
            config(["database.connections.{$connectionName}" => config("database.connections.{$defaultConnection}")]);
            DB::purge($connectionName);
            DB::setDefaultConnection($connectionName);

            fwrite($sockets[1], "ready\n");
            fgets($sockets[1]);

            try {
                $reviewer = User::query()->findOrFail($admin->id);
                app(ReviewVenueReservation::class)->handle($second->id, $reviewer, Reservation::STATUS_APPROVED);
                fwrite($sockets[1], "approved\n");
                exit(0);
            } catch (ValidationException) {
                fwrite($sockets[1], "conflict\n");
                exit(0);
            } catch (Throwable $exception) {
                fwrite($sockets[1], 'error: '.$exception::class."\n");
                exit(1);
            }
        }

        fclose($sockets[1]);
        stream_set_timeout($sockets[0], 15);
        $childResult = null;
        $finalStatuses = [];
        $status = 0;

        try {
            $this->assertSame("ready\n", fgets($sockets[0]));

            DB::transaction(function () use ($venue, $sockets, $first, $admin): void {
                Venue::query()->lockForUpdate()->findOrFail($venue->id);
                fwrite($sockets[0], "go\n");
                usleep(200_000);

                app(ReviewVenueReservation::class)->handle(
                    $first->id,
                    $admin,
                    Reservation::STATUS_APPROVED,
                );
            });

            $childResult = trim((string) fgets($sockets[0]));
            $finalStatuses = [
                Reservation::query()->find($first->id)?->status,
                Reservation::query()->find($second->id)?->status,
            ];
        } finally {
            fclose($sockets[0]);
            pcntl_waitpid($childPid, $status);
            Reservation::query()->whereIn('id', [$first->id, $second->id])->delete();
            Event::query()->whereIn('id', [$firstEvent->id, $secondEvent->id])->delete();
            Venue::query()->whereKey($venue->id)->delete();
            User::query()->whereIn('id', [$organizer->id, $admin->id])->delete();
        }

        $this->assertSame('conflict', $childResult);
        $this->assertSame(0, pcntl_wexitstatus($status));
        $this->assertSame([
            Reservation::STATUS_APPROVED,
            Reservation::STATUS_PENDING,
        ], $finalStatuses);
    }

    private function createEvent(User $organizer, string $title): Event
    {
        return Event::query()->create([
            'organizer_id' => $organizer->id,
            'club_name' => 'Concurrency Test Club',
            'title' => $title,
            'description' => 'Concurrency integration test.',
            'objectives' => 'Ensure reservation approvals serialize.',
            'starts_at' => '2027-06-10 10:00:00',
            'ends_at' => '2027-06-10 12:00:00',
            'proposal_status' => Event::STATUS_APPROVED,
        ]);
    }

    private function createReservation(Event $event, Venue $venue, User $organizer, string $start, string $end): Reservation
    {
        return Reservation::query()->create([
            'event_id' => $event->id,
            'venue_id' => $venue->id,
            'requester_id' => $organizer->id,
            'starts_at' => '2027-06-10 '.$start,
            'ends_at' => '2027-06-10 '.$end,
            'status' => Reservation::STATUS_PENDING,
        ]);
    }
}
