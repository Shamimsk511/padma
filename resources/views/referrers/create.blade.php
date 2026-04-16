@extends('layouts.modern-admin')

@section('title', 'Add Referrer')
@section('page_title', 'Add New Referrer')

@section('header_actions')
    <a href="{{ route('referrers.index') }}" class="btn modern-btn modern-btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Referrers
    </a>
@stop

@section('page_content')
    <div class="card modern-card">
        <div class="card-header modern-header success-header">
            <h3 class="card-title">
                <i class="fas fa-user-tag"></i> Referrer Information
            </h3>
        </div>

        <div class="card-body">
            @if ($errors->any())
                <div class="modern-alert modern-alert-danger mb-4">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <h5>Whoops! There were some problems with your input.</h5>
                        <ul class="error-list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('referrers.store') }}" method="POST" class="modern-form">
                @csrf

                <div class="form-grid">

                    <div class="form-group-modern full-width">
                        <label for="name" class="modern-label required">
                            <i class="fas fa-user"></i>
                            Referrer Name
                        </label>
                        <input type="text"
                               name="name"
                               id="name"
                               class="modern-input"
                               placeholder="Enter referrer name"
                               value="{{ old('name') }}"
                               required>
                    </div>

                    <div class="form-group-modern half-width">
                        <label for="phone" class="modern-label">
                            <i class="fas fa-phone"></i>
                            Phone
                        </label>
                        <input type="text"
                               name="phone"
                               id="phone"
                               class="modern-input"
                               placeholder="Enter phone number"
                               value="{{ old('phone') }}">
                    </div>

                    <div class="form-group-modern half-width">
                        <label for="profession" class="modern-label">
                            <i class="fas fa-briefcase"></i>
                            Profession
                        </label>
                        <input type="text"
                               name="profession"
                               id="profession"
                               class="modern-input"
                               placeholder="Enter profession"
                               value="{{ old('profession') }}">
                    </div>

                    <div class="form-group-modern full-width">
                        <label for="note" class="modern-label">
                            <i class="fas fa-sticky-note"></i>
                            Note
                        </label>
                        <textarea name="note"
                                  id="note"
                                  class="modern-input"
                                  rows="3"
                                  placeholder="Enter any notes">{{ old('note') }}</textarea>
                    </div>

                    <div class="form-group-modern half-width">
                        <label class="modern-label">
                            <i class="fas fa-hand-holding-usd"></i>
                            Compensation
                        </label>
                        <div class="custom-control custom-switch mt-1">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="compensation_enabled"
                                   name="compensation_enabled"
                                   value="1"
                                   {{ old('compensation_enabled') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="compensation_enabled">Enable Compensation</label>
                        </div>
                    </div>

                    <div class="form-group-modern half-width">
                        <label class="modern-label">
                            <i class="fas fa-gift"></i>
                            Gift
                        </label>
                        <div class="custom-control custom-switch mt-1">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="gift_enabled"
                                   name="gift_enabled"
                                   value="1"
                                   {{ old('gift_enabled') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="gift_enabled">Enable Gift</label>
                        </div>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="submit" class="btn modern-btn modern-btn-success">
                        <i class="fas fa-save"></i> Create Referrer
                    </button>
                    <a href="{{ route('referrers.index') }}" class="btn modern-btn modern-btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@stop

@section('additional_css')
<style>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.form-group-modern.full-width {
    grid-column: 1 / -1;
}

.form-group-modern.half-width {
    grid-column: span 1;
}

.form-group-modern {
    margin-bottom: 0;
}

.modern-form {
    padding: 0;
}

.modern-label {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.modern-label i {
    margin-right: 0.5rem;
    color: var(--primary-color);
    width: 16px;
}

.modern-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: white;
}

.modern-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.modern-alert {
    display: flex;
    align-items: flex-start;
    padding: 1rem 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 1.5rem;
}

.modern-alert-danger {
    background: linear-gradient(135deg, #fee 0%, #fdd 100%);
    border-left: 4px solid #dc3545;
}

.alert-icon {
    margin-right: 1rem;
    font-size: 1.25rem;
    color: #dc3545;
}

.alert-content h5 {
    margin: 0 0 0.5rem 0;
    color: #721c24;
    font-weight: 600;
}

.error-list {
    margin: 0;
    padding-left: 1.25rem;
    color: #721c24;
}

.error-list li {
    margin-bottom: 0.25rem;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    .form-group-modern.half-width {
        grid-column: 1;
    }
    .form-actions {
        flex-direction: column;
    }
}
</style>
@stop
