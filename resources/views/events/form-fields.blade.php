@php($event = $event ?? null)
<div class="mb-3">
    <label class="form-label" for="club_name">Club name</label>
    <input class="form-control" id="club_name" name="club_name" value="{{ old('club_name', $event?->club_name) }}" required maxlength="255">
    @error('club_name')<div class="text-danger">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="title">Event title</label>
    <input class="form-control" id="title" name="title" value="{{ old('title', $event?->title) }}" required maxlength="255">
    @error('title')<div class="text-danger">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <textarea class="form-control" id="description" name="description" rows="4" required>{{ old('description', $event?->description) }}</textarea>
    @error('description')<div class="text-danger">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="objectives">Objectives</label>
    <textarea class="form-control" id="objectives" name="objectives" rows="3" required>{{ old('objectives', $event?->objectives) }}</textarea>
    @error('objectives')<div class="text-danger">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="requirements">Requirements</label>
    <textarea class="form-control" id="requirements" name="requirements" rows="3">{{ old('requirements', $event?->requirements) }}</textarea>
    @error('requirements')<div class="text-danger">{{ $message }}</div>@enderror
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label" for="starts_at">Proposed start</label>
        <input class="form-control" id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $event?->starts_at?->format('Y-m-d\TH:i')) }}" required>
        @error('starts_at')<div class="text-danger">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="ends_at">Proposed end</label>
        <input class="form-control" id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $event?->ends_at?->format('Y-m-d\TH:i')) }}" required>
        @error('ends_at')<div class="text-danger">{{ $message }}</div>@enderror
    </div>
</div>
