@extends('layouts.app')

@section('title', 'Create Schedule - Nexus BMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0 text-white">Create Schedule</h2>
        <p class="text-muted small mb-0">Define an automation schedule for equipment</p>
    </div>
    <a href="{{ route('schedules.index') }}" class="nx-btn nx-btn-outline">
        <i class="fa-solid fa-arrow-left me-2"></i>Back
    </a>
</div>

<div class="nx-card p-4" style="max-width: 1100px;">
    <form action="{{ route('schedules.store') }}" method="POST">
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select name="category" class="form-select" required>
                    <option value="">&mdash; Select Category &mdash;</option>
                    @foreach (['HVAC','Lighting','Access Control','Maintenance','General'] as $cat)
                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Building</label>
                <select name="building_id" class="form-select">
                    <option value="">&mdash; Any Building &mdash;</option>
                    @foreach ($buildings as $b)
                        <option value="{{ $b->id }}" {{ old('building_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Floor</label>
                <input type="text" name="floor_id" class="form-control" value="{{ old('floor_id') }}" placeholder="Optional floor reference">
            </div>

            <div class="col-md-4">
                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label">Turn On <span class="text-danger">*</span></label>
                <input type="time" name="turn_on_time" class="form-control" value="{{ old('turn_on_time') }}" required>
            </div>

            <div class="col-md-2">
                <label class="form-label">Turn Off</label>
                <input type="time" name="turn_off_time" class="form-control" value="{{ old('turn_off_time') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Recurrence <span class="text-danger">*</span></label>
                <select name="recurrence" class="form-select" required>
                    @foreach (['daily','weekly','monthly','once'] as $r)
                        <option value="{{ $r }}" {{ old('recurrence', 'weekly') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    @foreach (['active','inactive','disabled'] as $s)
                        <option value="{{ $s }}" {{ old('status', 'active') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-12">
                <label class="form-label">Repeat Days</label>
                <div class="d-flex flex-wrap gap-2">
                    @php $days = ['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun']; @endphp
                    @foreach ($days as $key => $label)
                        <div class="form-check form-check-inline">
                            <input type="checkbox" id="day_{{ $key }}" name="repeat_days[]" value="{{ $key }}" class="form-check-input"
                                {{ in_array($key, old('repeat_days', [])) ? 'checked' : '' }}>
                            <label for="day_{{ $key }}" class="form-check-label">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-md-12">
                <label class="form-label">Equipment</label>
                <input type="text" id="eq_search" class="form-control mb-2" placeholder="Search equipment...">
                <div style="max-height: 300px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 0.75rem;">
                    @forelse ($equipment as $eq)
                        <label class="form-check d-block equipment-pick-item mb-1">
                            <input type="checkbox" name="equipment_ids[]" value="{{ $eq->id }}" class="form-check-input"
                                {{ in_array($eq->id, old('equipment_ids', [])) ? 'checked' : '' }}>
                            {{ $eq->code }} - {{ $eq->name }}
                            <small class="text-muted">({{ optional($eq->category)->name }} / {{ optional($eq->building)->name }})</small>
                        </label>
                    @empty
                        <p class="text-muted small mb-0">No equipment available.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="nx-btn nx-btn-primary">
                <i class="fa-solid fa-save me-2"></i>Save Schedule
            </button>
            <a href="{{ route('schedules.index') }}" class="nx-btn nx-btn-outline">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('eq_search')?.addEventListener('input', function(){
    const term = this.value.toLowerCase();
    document.querySelectorAll('.equipment-pick-item').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});
</script>
@endpush
