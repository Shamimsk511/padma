@extends('layouts.modern-admin')

@section('title', 'Business Settings')
@section('page_title', 'Business Settings Configuration')

@section('header_actions')
    <button type="button" class="btn modern-btn modern-btn-info" onclick="previewSettings()">
        <i class="fas fa-eye"></i> Preview
    </button>
@stop

@section('page_content')
    @if(session('success'))
        <div class="alert modern-alert modern-alert-success" id="success-alert">
            <div class="alert-content">
                <i class="fas fa-check-circle alert-icon"></i>
                <div class="alert-message">
                    <strong>Success!</strong>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" class="alert-close" onclick="this.parentElement.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert modern-alert modern-alert-error" id="error-alert">
            <div class="alert-content">
                <i class="fas fa-exclamation-triangle alert-icon"></i>
                <div class="alert-message">
                    <strong>Error!</strong>
                    <span>Please check the form for errors</span>
                </div>
                <button type="button" class="alert-close" onclick="this.parentElement.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    <section class="settings-hero mb-4">
        <div class="settings-hero__content">
            <div>
                <p class="hero-eyebrow">Configuration</p>
                <h2 class="hero-title">Business Settings</h2>
                <p class="hero-subtitle">Tune identity, finance, system preferences, and interface styling for your company.</p>
                @if(isset($tenantList) && $tenantList)
                    <span class="hero-pill">
                        Editing: {{ $tenantList->firstWhere('id', $selectedTenantId)?->name ?? 'Selected Company' }}
                    </span>
                @endif
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat__label">Active Theme</span>
                    <span class="hero-stat__value">
                        {{ data_get($themes, old('theme', $settings->theme ?? 'indigo') . '.name', 'Default') }}
                    </span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat__label">Timezone</span>
                    <span class="hero-stat__value">{{ $settings->timezone ?? 'Asia/Dhaka' }}</span>
                </div>
            </div>
        </div>
        <div class="settings-hero__accent"></div>
    </section>

    @if(isset($tenantList) && $tenantList)
        <div class="card modern-card mb-4">
            <div class="card-header modern-header system-header">
                <h3 class="card-title">
                    <i class="fas fa-building"></i> Company Context
                </h3>
            </div>
            <div class="card-body modern-card-body">
                <form method="GET" action="{{ route('business-settings.index') }}" class="tenant-switch-form">
                    <div class="form-group modern-form-group">
                        <label for="tenant_id" class="modern-label">Select Company</label>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <select name="tenant_id" id="tenant_id" class="form-control modern-select" style="max-width: 320px;">
                                @foreach($tenantList as $tenant)
                                    <option value="{{ $tenant->id }}" {{ (int) $selectedTenantId === (int) $tenant->id ? 'selected' : '' }}>
                                        {{ $tenant->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn modern-btn modern-btn-primary">Load Settings</button>
                        </div>
                        <small class="text-muted d-block mt-2">
                            You are editing settings for <strong>{{ $tenantList->firstWhere('id', $selectedTenantId)?->name ?? 'Selected Company' }}</strong>.
                        </small>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <form action="{{ route('business-settings.update') }}" method="POST" enctype="multipart/form-data" id="settingsForm">
        @csrf
        @method('PUT')
        @if(isset($tenantList) && $tenantList && !empty($selectedTenantId))
            <input type="hidden" name="tenant_id" value="{{ $selectedTenantId }}">
        @endif

        <div class="card modern-card mb-0">
            <div class="card-header modern-header p-0" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border-radius: var(--border-radius) var(--border-radius) 0 0;">
                <ul class="nav nav-pills settings-nav-pills" id="settingsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-general-link" data-toggle="pill" href="#tab-general" role="tab">
                            <i class="fas fa-building"></i> General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-branding-link" data-toggle="pill" href="#tab-branding" role="tab">
                            <i class="fas fa-palette"></i> Branding
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-invoice-link" data-toggle="pill" href="#tab-invoice" role="tab">
                            <i class="fas fa-file-invoice"></i> Invoice
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-system-link" data-toggle="pill" href="#tab-system" role="tab">
                            <i class="fas fa-cogs"></i> System
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content" id="settingsTabContent">

                    {{-- ========== TAB: GENERAL ========== --}}
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                        <div class="settings-tab-body">
                            <div class="settings-section-title">
                                <i class="fas fa-id-card"></i> Company Information
                            </div>
                            <div class="form-grid">
                                <div class="form-group-modern full-width">
                                    <label for="business_name" class="modern-label required">
                                        <i class="fas fa-building"></i> Business Name <span class="required-star">*</span>
                                    </label>
                                    <input type="text" name="business_name" id="business_name"
                                           class="modern-input @error('business_name') is-invalid @enderror"
                                           value="{{ old('business_name', $settings->business_name) }}" required>
                                    @error('business_name')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group-modern half-width">
                                    <label for="email" class="modern-label">
                                        <i class="fas fa-envelope"></i> Email Address
                                    </label>
                                    <input type="email" name="email" id="email"
                                           class="modern-input @error('email') is-invalid @enderror"
                                           value="{{ old('email', $settings->email) }}">
                                    @error('email')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group-modern half-width">
                                    <label for="phone" class="modern-label required">
                                        <i class="fas fa-phone"></i> Phone Number <span class="required-star">*</span>
                                    </label>
                                    <input type="text" name="phone" id="phone"
                                           class="modern-input @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $settings->phone) }}" required>
                                    @error('phone')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group-modern half-width">
                                    <label for="bin_number" class="modern-label">
                                        <i class="fas fa-id-card"></i> BIN Number
                                    </label>
                                    <input type="text" name="bin_number" id="bin_number"
                                           class="modern-input @error('bin_number') is-invalid @enderror"
                                           value="{{ old('bin_number', $settings->bin_number) }}">
                                    @error('bin_number')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group-modern half-width">
                                    <label for="address" class="modern-label">
                                        <i class="fas fa-map-marker-alt"></i> Address
                                    </label>
                                    <textarea name="address" id="address"
                                              class="modern-input @error('address') is-invalid @enderror"
                                              rows="3">{{ old('address', $settings->address) }}</textarea>
                                    @error('address')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="settings-section-title mt-3">
                                <i class="fas fa-university"></i> Financial Information
                            </div>
                            <div class="form-grid">
                                <div class="form-group-modern half-width">
                                    <label for="bank_details" class="modern-label">
                                        <i class="fas fa-university"></i> Bank Details
                                    </label>
                                    <textarea name="bank_details" id="bank_details"
                                              class="modern-input @error('bank_details') is-invalid @enderror"
                                              rows="4" placeholder="Enter bank account details, routing numbers, etc.">{{ old('bank_details', $settings->bank_details) }}</textarea>
                                    @error('bank_details')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group-modern half-width">
                                    <label for="footer_message" class="modern-label">
                                        <i class="fas fa-quote-right"></i> Footer Message
                                    </label>
                                    <textarea name="footer_message" id="footer_message"
                                              class="modern-input @error('footer_message') is-invalid @enderror"
                                              rows="4">{{ old('footer_message', $settings->footer_message) }}</textarea>
                                    @error('footer_message')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">Shows on invoices and printouts.</small>
                                </div>

                                <div class="form-group-modern half-width">
                                    <label for="return_policy_days" class="modern-label">
                                        <i class="fas fa-shield-alt"></i> Return Policy Days
                                    </label>
                                    <div class="input-group modern-input-group">
                                        <input type="number" name="return_policy_days" id="return_policy_days"
                                               class="form-control modern-input @error('return_policy_days') is-invalid @enderror"
                                               value="{{ old('return_policy_days', $settings->return_policy_days) }}" min="0">
                                        <div class="input-group-append">
                                            <span class="input-group-text modern-input-addon">days</span>
                                        </div>
                                    </div>
                                    @error('return_policy_days')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group-modern half-width">
                                    <label for="return_policy_message" class="modern-label">
                                        <i class="fas fa-comment-alt"></i> Return Policy Message
                                    </label>
                                    <input type="text" name="return_policy_message" id="return_policy_message"
                                           class="modern-input @error('return_policy_message') is-invalid @enderror"
                                           value="{{ old('return_policy_message', $settings->return_policy_message) }}"
                                           placeholder="e.g., Items can be returned within specified days">
                                    @error('return_policy_message')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ========== TAB: BRANDING ========== --}}
                    <div class="tab-pane fade" id="tab-branding" role="tabpanel">
                        <div class="settings-tab-body">
                            <div class="settings-section-title">
                                <i class="fas fa-image"></i> Logo
                            </div>
                            <div class="form-grid">
                                <div class="form-group-modern full-width">
                                    <label for="logo" class="modern-label">
                                        <i class="fas fa-image"></i> Company Logo
                                    </label>
                                    @if($settings->logo)
                                        <div class="current-logo-container mb-3">
                                            <div class="current-logo-preview">
                                                <img src="{{ Storage::url($settings->logo) }}" alt="Current Logo" class="current-logo">
                                                <div class="logo-overlay">
                                                    <span class="logo-text">Current Logo</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="modern-file-upload">
                                        <div class="file-upload-container">
                                            <input type="file" name="logo" id="logo" class="file-input" accept="image/*">
                                            <label for="logo" class="file-upload-label">
                                                <div class="file-upload-content">
                                                    <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                                                    <span class="file-upload-text">Choose Logo File</span>
                                                    <span class="file-upload-subtext">or drag and drop</span>
                                                </div>
                                            </label>
                                        </div>
                                        <div class="file-upload-info">
                                            <small class="text-muted">Recommended: 200x200px. Formats: JPG, PNG, SVG</small>
                                        </div>
                                    </div>
                                    @error('logo')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="settings-section-title mt-3">
                                <i class="fas fa-palette"></i> Theme & Appearance
                            </div>

                            @php
                                $currentThemeKey = old('theme', $settings->theme ?? 'indigo');
                                $currentThemeName = data_get($themes, $currentThemeKey . '.name', 'Default');
                                $gradientThemes = [];
                                $solidThemes = [];
                                foreach ($themes as $key => $theme) {
                                    $style = $theme['style'] ?? 'gradient';
                                    if ($style === 'solid') {
                                        $solidThemes[$key] = $theme;
                                    } else {
                                        $gradientThemes[$key] = $theme;
                                    }
                                }
                            @endphp

                            <div class="theme-intro">
                                <div>
                                    <p class="text-muted mb-1">Choose a theme to style the entire application interface.</p>
                                    <small class="text-muted">Solid colors are lightweight and ideal for faster rendering.</small>
                                </div>
                                <div class="theme-active-badge">
                                    <i class="fas fa-check"></i> Active: <span id="active-theme-name">{{ $currentThemeName }}</span>
                                </div>
                            </div>

                            <div class="theme-section">
                                <div class="theme-section__header">
                                    <h6>Gradient Themes</h6>
                                    <span class="theme-section__meta">{{ count($gradientThemes) }} options</span>
                                </div>
                                <div class="theme-grid">
                                    @forelse($gradientThemes as $key => $theme)
                                        <label class="theme-card" data-style="{{ $theme['style'] ?? 'gradient' }}" data-theme-name="{{ $theme['name'] }}">
                                            <input type="radio" name="theme" value="{{ $key }}"
                                                   {{ $currentThemeKey === $key ? 'checked' : '' }}>
                                            <div class="theme-card__visual"
                                                 style="--theme-primary: {{ $theme['primary'] }};
                                                        --theme-accent: {{ $theme['accent'] }};
                                                        --theme-bg: {{ $theme['bg'] }};">
                                                <span class="theme-dot primary"></span>
                                                <span class="theme-dot accent"></span>
                                                <span class="theme-swatch"></span>
                                            </div>
                                            <div class="theme-card__content">
                                                <div class="theme-name">{{ $theme['name'] }}</div>
                                                <div class="theme-meta">{{ strtoupper($key) }}</div>
                                            </div>
                                        </label>
                                    @empty
                                        <div class="text-muted">No gradient themes available.</div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="theme-section">
                                <div class="theme-section__header">
                                    <h6>Solid Colors</h6>
                                    <span class="theme-section__meta">{{ count($solidThemes) }} options</span>
                                </div>
                                <div class="theme-grid theme-grid--compact">
                                    @forelse($solidThemes as $key => $theme)
                                        <label class="theme-card" data-style="{{ $theme['style'] ?? 'solid' }}" data-theme-name="{{ $theme['name'] }}">
                                            <input type="radio" name="theme" value="{{ $key }}"
                                                   {{ $currentThemeKey === $key ? 'checked' : '' }}>
                                            <div class="theme-card__visual"
                                                 style="--theme-primary: {{ $theme['primary'] }};
                                                        --theme-accent: {{ $theme['accent'] }};
                                                        --theme-bg: {{ $theme['bg'] }};">
                                                <span class="theme-dot primary"></span>
                                                <span class="theme-dot accent"></span>
                                                <span class="theme-swatch"></span>
                                            </div>
                                            <div class="theme-card__content">
                                                <div class="theme-name">{{ $theme['name'] }}</div>
                                                <div class="theme-meta">{{ strtoupper($key) }}</div>
                                            </div>
                                        </label>
                                    @empty
                                        <div class="text-muted">No solid themes available.</div>
                                    @endforelse
                                </div>
                            </div>
                            @error('theme')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- ========== TAB: INVOICE ========== --}}
                    <div class="tab-pane fade" id="tab-invoice" role="tabpanel">
                        <div class="settings-tab-body">
                            <div class="settings-section-title">
                                <i class="fas fa-file-invoice"></i> Invoice Template Studio
                            </div>

                            <ul class="nav nav-tabs invoice-tabs" id="invoice-template-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="invoice-layout-tab" data-toggle="tab" href="#invoice-layout-pane" role="tab">
                                        Layout & Visibility
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="invoice-preview-tab" data-toggle="tab" href="#invoice-preview-pane" role="tab">
                                        Template Preview
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content invoice-tab-content" id="invoice-template-tab-content">
                                <div class="tab-pane fade show active" id="invoice-layout-pane" role="tabpanel">
                                    <div class="form-grid">
                                        <div class="form-group-modern half-width">
                                            <label for="invoice_template" class="modern-label">
                                                <i class="fas fa-file-alt"></i> Default Invoice Template
                                            </label>
                                            <select name="invoice_template" id="invoice_template" class="modern-input modern-select">
                                                @foreach($invoiceTemplates as $key => $label)
                                                    <option value="{{ $key }}" {{ old('invoice_template', $settings->invoice_template ?? 'standard') === $key ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted d-block mt-1">Default print style for invoices, challans, ledger, returns.</small>
                                        </div>

                                        <div class="form-group-modern half-width">
                                            <label for="invoice_phone_override" class="modern-label">
                                                <i class="fas fa-phone-alt"></i> Invoice Phone Override
                                            </label>
                                            <input type="text"
                                                   name="invoice_print_options[invoice_phone_override]"
                                                   id="invoice_phone_override"
                                                   class="modern-input"
                                                   value="{{ old('invoice_print_options.invoice_phone_override', $invoicePrintOptions['invoice_phone_override'] ?? '') }}"
                                                   placeholder="Leave blank to use business phone">
                                            <small class="text-muted d-block mt-1">Only changes the phone shown on invoice prints.</small>
                                        </div>
                                    </div>

                                    @php
                                        $invoiceToggleItems = [
                                            'show_company_phone'   => ['Company Phone',      'fas fa-phone'],
                                            'show_company_email'   => ['Company Email',       'fas fa-envelope'],
                                            'show_company_address' => ['Company Address',     'fas fa-map-marker-alt'],
                                            'show_company_bin'     => ['Company BIN',         'fas fa-id-card'],
                                            'show_bank_details'    => ['Bank Details',        'fas fa-university'],
                                            'show_terms'           => ['Terms & Conditions',  'fas fa-file-contract'],
                                            'show_footer_message'  => ['Footer Message',      'fas fa-quote-right'],
                                            'show_customer_qr'     => ['Customer QR Block',   'fas fa-qrcode'],
                                            'show_signatures'      => ['Signature Section',   'fas fa-signature'],
                                        ];
                                        $truthyValues = [1, '1', true, 'true', 'on'];
                                    @endphp

                                    <div class="row">
                                        @foreach($invoiceToggleItems as $optionKey => [$optionLabel, $optionIcon])
                                            @php
                                                $optionValue = old("invoice_print_options.$optionKey", data_get($invoicePrintOptions, $optionKey, true));
                                                $isChecked = in_array($optionValue, $truthyValues, true);
                                            @endphp
                                            <div class="col-lg-4 col-md-6">
                                                <div class="invoice-toggle-item">
                                                    <input type="hidden" name="invoice_print_options[{{ $optionKey }}]" value="0">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox"
                                                               class="custom-control-input invoice-toggle"
                                                               id="{{ $optionKey }}"
                                                               name="invoice_print_options[{{ $optionKey }}]"
                                                               value="1"
                                                               {{ $isChecked ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="{{ $optionKey }}">
                                                            <i class="{{ $optionIcon }}"></i> {{ $optionLabel }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="invoice-preview-pane" role="tabpanel">
                                    @if($previewInvoiceId)
                                        <div class="alert modern-alert modern-alert-info mb-3">
                                            <div class="alert-content">
                                                <i class="fas fa-info-circle alert-icon"></i>
                                                <div class="alert-message">
                                                    <strong>Preview Ready</strong>
                                                    <span>Templates open using your latest invoice. Save to apply default system-wide.</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="template-preview-grid">
                                            @foreach($invoiceTemplates as $key => $label)
                                                <button type="button" class="btn modern-btn modern-btn-secondary invoice-preview-btn" data-template="{{ $key }}">
                                                    <i class="fas fa-eye"></i> Preview {{ $label }}
                                                </button>
                                            @endforeach
                                        </div>
                                        <div class="mt-3">
                                            <button type="button" class="btn modern-btn modern-btn-primary" id="preview-current-template">
                                                <i class="fas fa-external-link-alt"></i> Preview Current Selection
                                            </button>
                                        </div>
                                    @else
                                        <div class="alert modern-alert modern-alert-error">
                                            <div class="alert-content">
                                                <i class="fas fa-exclamation-triangle alert-icon"></i>
                                                <div class="alert-message">
                                                    <strong>No Invoice Found</strong>
                                                    <span>Create at least one invoice to use live template preview.</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ========== TAB: SYSTEM ========== --}}
                    <div class="tab-pane fade" id="tab-system" role="tabpanel">
                        <div class="settings-tab-body">
                            <div class="settings-section-title">
                                <i class="fas fa-globe"></i> Timezone & Schedule
                            </div>
                            <div class="form-grid">
                                <div class="form-group-modern half-width">
                                    <label for="timezone" class="modern-label">
                                        <i class="fas fa-globe"></i> Timezone
                                    </label>
                                    <select name="timezone" id="timezone"
                                            class="form-control modern-input modern-select @error('timezone') is-invalid @enderror">
                                        @foreach($timezones as $region => $zones)
                                            <optgroup label="{{ $region }}">
                                                @foreach($zones as $tz => $label)
                                                    <option value="{{ $tz }}" {{ old('timezone', $settings->timezone ?? 'Asia/Dhaka') == $tz ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('timezone')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted mt-1 d-block">
                                        <i class="fas fa-info-circle"></i>
                                        Server time: <strong id="current-server-time">{{ now()->format('Y-m-d h:i:s A') }}</strong>
                                    </small>
                                </div>

                                <div class="form-group-modern half-width">
                                    <label class="modern-label">
                                        <i class="fas fa-clock"></i> Time Preview
                                    </label>
                                    <div class="timezone-preview-box">
                                        <div class="preview-time" id="preview-time">
                                            <span class="time-display">--:--:--</span>
                                            <span class="date-display">----/--/--</span>
                                        </div>
                                        <div class="preview-label">Selected timezone time</div>
                                    </div>
                                </div>

                                <div class="form-group-modern half-width">
                                    <label for="customer_qr_expiry_days" class="modern-label">
                                        <i class="fas fa-qrcode"></i> Customer QR Expiry (days)
                                    </label>
                                    <div class="input-group modern-input-group">
                                        <input type="number" name="customer_qr_expiry_days" id="customer_qr_expiry_days"
                                               class="form-control modern-input @error('customer_qr_expiry_days') is-invalid @enderror"
                                               value="{{ old('customer_qr_expiry_days', $settings->customer_qr_expiry_days ?? 30) }}" min="1" max="3650">
                                        <div class="input-group-append">
                                            <span class="input-group-text modern-input-addon">days</span>
                                        </div>
                                    </div>
                                    @error('customer_qr_expiry_days')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted mt-1 d-block">Controls how long invoice QR login links remain valid.</small>
                                </div>

                                <div class="form-group-modern half-width">
                                    <label class="modern-label">
                                        <i class="fas fa-truck"></i> Undelivered Invoices Alert
                                    </label>
                                    <div class="invoice-toggle-item">
                                        <input type="hidden" name="delivery_alert_enabled" value="0">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox"
                                                   class="custom-control-input"
                                                   id="delivery_alert_enabled"
                                                   name="delivery_alert_enabled"
                                                   value="1"
                                                   {{ old('delivery_alert_enabled', $settings->delivery_alert_enabled ?? true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="delivery_alert_enabled">
                                                <i class="fas fa-bell"></i> Show alert modal on login when there are undelivered invoices
                                            </label>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-1">When enabled, a popup appears after login listing yesterday's and older pending/partial deliveries.</small>
                                </div>

                                <div class="form-group-modern half-width">
                                    <label class="modern-label">
                                        <i class="fas fa-calendar-week"></i> Weekend Days
                                    </label>
                                    <div class="weekend-grid">
                                        @php
                                            $weekendDays = old('weekend_days', $settings->weekend_days ?? ['Friday']);
                                        @endphp
                                        @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
                                            <label class="weekend-pill">
                                                <input type="checkbox" name="weekend_days[]" value="{{ $day }}"
                                                       {{ in_array($day, $weekendDays) ? 'checked' : '' }}>
                                                <span>{{ $day }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('weekend_days')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                </div>{{-- end tab-content --}}

                {{-- Save bar --}}
                <div class="settings-save-bar">
                    <button type="submit" class="btn modern-btn modern-btn-primary" id="submit-btn">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                    <button type="button" class="btn modern-btn modern-btn-secondary" onclick="resetForm()">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>

            </div>{{-- end card-body --}}
        </div>{{-- end card --}}
    </form>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content modern-modal">
                <div class="modal-header modern-modal-header">
                    <h5 class="modal-title" id="previewModalLabel">
                        <i class="fas fa-eye"></i> Settings Preview
                    </h5>
                    <button type="button" class="close modern-close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body modern-modal-body">
                    <div class="preview-content">
                        <div class="preview-section">
                            <h6 class="preview-title">Company Information</h6>
                            <div class="preview-item"><strong>Business Name:</strong> <span id="preview-business-name">-</span></div>
                            <div class="preview-item"><strong>Email:</strong> <span id="preview-email">-</span></div>
                            <div class="preview-item"><strong>Phone:</strong> <span id="preview-phone">-</span></div>
                            <div class="preview-item"><strong>Address:</strong> <span id="preview-address">-</span></div>
                        </div>
                        <div class="preview-section">
                            <h6 class="preview-title">Return Policy</h6>
                            <div class="preview-item"><strong>Days:</strong> <span id="preview-return-days">-</span></div>
                            <div class="preview-item"><strong>Message:</strong> <span id="preview-return-message">-</span></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modern-modal-footer">
                    <button type="button" class="btn modern-btn modern-btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('additional_css')
<style>
    body, .content-wrapper { overflow-x: hidden; }
    .settings-hero, .modern-card, .theme-grid, .theme-card, .file-upload-container { max-width: 100%; }

    /* Hero */
    .settings-hero {
        background: linear-gradient(135deg, var(--app-primary, #1d4ed8) 0%, var(--app-accent, #60a5fa) 100%);
        border-radius: 20px; color: #e0f2fe; padding: 24px 28px;
        position: relative; overflow: hidden; box-shadow: 0 18px 45px rgba(15,23,42,.25);
    }
    .settings-hero__content { display: flex; flex-wrap: wrap; gap: 24px; align-items: center; justify-content: space-between; position: relative; z-index: 1; }
    .settings-hero__accent { position: absolute; inset: -40% -10% auto auto; width: 220px; height: 220px; background: radial-gradient(circle, rgba(255,255,255,.35), rgba(255,255,255,0)); }
    .hero-eyebrow { text-transform: uppercase; letter-spacing: .2em; font-size: 12px; margin-bottom: 8px; color: rgba(226,232,240,.75); }
    .hero-title { font-size: 28px; font-weight: 700; margin-bottom: 6px; color: #fff; }
    .hero-subtitle { margin: 0; max-width: 520px; color: rgba(226,232,240,.9); }
    .hero-pill { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px; background: rgba(15,23,42,.35); color: #e2e8f0; font-size: 12px; margin-top: 12px; }
    .hero-stats { display: grid; gap: 12px; background: rgba(15,23,42,.25); border-radius: 14px; padding: 14px 18px; min-width: 220px; }
    .hero-stat { display: flex; flex-direction: column; gap: 4px; }
    .hero-stat__label { font-size: 11px; text-transform: uppercase; letter-spacing: .18em; color: rgba(226,232,240,.7); }
    .hero-stat__value { font-size: 14px; font-weight: 600; color: #fff; }

    /* Tab nav pills */
    .settings-nav-pills { padding: 10px 16px; gap: 4px; flex-wrap: wrap; }
    .settings-nav-pills .nav-item { margin: 0; }
    .settings-nav-pills .nav-link {
        color: rgba(255,255,255,.75) !important; font-weight: 600; font-size: 0.88rem;
        padding: 8px 16px; border-radius: 8px; transition: all .2s ease;
        display: flex; align-items: center; gap: 6px;
        background: transparent !important;
    }
    .settings-nav-pills .nav-link i { font-size: 0.85rem; }
    .settings-nav-pills .nav-link:hover { color: #fff !important; background: rgba(255,255,255,.15) !important; }
    .settings-nav-pills .nav-link.active,
    .settings-nav-pills .nav-link.active:focus,
    .settings-nav-pills .nav-link.active:hover {
        color: #1e293b !important;
        background: #ffffff !important;
        box-shadow: 0 2px 10px rgba(0,0,0,.25);
    }

    /* Tab body */
    .settings-tab-body { padding: 28px; }
    .settings-section-title {
        display: flex; align-items: center; gap: 8px;
        font-size: 0.95rem; font-weight: 700; color: #374151;
        padding-bottom: 10px; margin-bottom: 20px;
        border-bottom: 2px solid #e5e7eb;
    }
    .settings-section-title i { color: var(--primary-color, #6366f1); }

    /* Save bar */
    .settings-save-bar {
        display: flex; gap: 1rem; justify-content: center;
        padding: 16px 28px; border-top: 1px solid #e5e7eb;
        background: #f8fafc;
        border-radius: 0 0 var(--border-radius, 12px) var(--border-radius, 12px);
    }

    /* Form grid */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1rem; }
    .form-group-modern { margin-bottom: 0; display: flex; flex-direction: column; }
    .form-group-modern.full-width { grid-column: 1 / -1; }
    .form-group-modern.half-width { grid-column: span 1; }

    /* Labels & inputs */
    .modern-label { display: flex; align-items: center; gap: 6px; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 0.9rem; }
    .modern-label i { color: var(--primary-color, #6366f1); width: 16px; flex-shrink: 0; }
    .required-star { color: #ef4444; }
    .modern-input, .modern-textarea {
        width: 100%; padding: 0.75rem 1rem; border: 2px solid #e5e7eb;
        border-radius: var(--border-radius, 10px); font-size: 0.95rem;
        transition: all .2s ease; background: white; color: #374151;
    }
    .modern-input:focus, .modern-textarea:focus {
        outline: none; border-color: var(--primary-color, #6366f1);
        box-shadow: 0 0 0 3px rgba(99,102,241,.1);
    }
    .modern-input.is-invalid { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.1); }
    .field-error { color: #ef4444; font-size: 12px; margin-top: 4px; }

    /* Input group */
    .modern-input-group { display: flex; border-radius: var(--border-radius, 10px); overflow: hidden; }
    .modern-input-group .modern-input { border-radius: 0; border-right: none; margin: 0; }
    .modern-input-addon { background: #f1f5f9; border: 2px solid #e5e7eb; border-left: none; color: #6b7280; font-weight: 600; font-size: 14px; padding: 0.75rem 1rem; display: flex; align-items: center; }

    /* Modern select */
    .modern-select { border: 2px solid #e5e7eb; border-radius: var(--border-radius, 10px); padding: 10px 16px; font-size: 14px; min-height: 44px; background: white; color: #374151; }
    .modern-select:focus { outline: none; border-color: var(--primary-color, #6366f1); box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
    .modern-select optgroup { font-weight: 600; color: #374151; background: #f8fafc; }

    /* File upload */
    .modern-file-upload { position: relative; }
    .file-upload-container { position: relative; border: 2px dashed #d1d5db; border-radius: 12px; padding: 40px 20px; text-align: center; transition: all .3s ease; background: linear-gradient(135deg, #f8fafc, #f1f5f9); }
    .file-upload-container:hover { border-color: #6366f1; background: linear-gradient(135deg, rgba(99,102,241,.05), rgba(139,92,246,.02)); }
    .file-upload-container.drag-over { border-color: #60a5fa; background: linear-gradient(135deg, rgba(59,130,246,.08), rgba(14,165,233,.04)); }
    .file-input { position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
    .file-upload-label { cursor: pointer; margin: 0; }
    .file-upload-content { display: flex; flex-direction: column; align-items: center; gap: 8px; }
    .file-upload-icon { font-size: 32px; color: #6366f1; margin-bottom: 8px; }
    .file-upload-text { font-size: 16px; font-weight: 600; color: #374151; }
    .file-upload-subtext { font-size: 14px; color: #6b7280; }
    .file-upload-info { margin-top: 12px; text-align: center; }
    .current-logo-container { display: flex; justify-content: center; }
    .current-logo-preview { position: relative; display: inline-block; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1); }
    .current-logo { max-height: 120px; max-width: 200px; display: block; }
    .logo-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,.7), transparent); color: white; padding: 8px 12px; text-align: center; }
    .logo-text { font-size: 12px; font-weight: 600; }

    /* Theme picker */
    .theme-intro { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; margin-bottom: 20px; }
    .theme-active-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-weight: 600; font-size: 12px; }
    .theme-section { padding: 16px 0 12px; border-top: 1px solid #e5e7eb; }
    .theme-section:first-of-type { border-top: none; padding-top: 0; }
    .theme-section__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .theme-section__header h6 { margin: 0; font-weight: 600; color: #111827; }
    .theme-section__meta { font-size: 12px; color: #6b7280; letter-spacing: .08em; text-transform: uppercase; }
    .theme-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
    .theme-grid--compact { grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }
    .theme-card { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 14px; border: 2px solid #e5e7eb; background: #fff; cursor: pointer; transition: all .2s ease; position: relative; }
    .theme-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
    .theme-card__visual { height: 44px; width: 44px; border-radius: 14px; background: var(--theme-bg); display: grid; place-items: center; position: relative; border: 1px solid rgba(0,0,0,.05); }
    .theme-card[data-style="solid"] .theme-card__visual { background: var(--theme-primary); }
    .theme-card[data-style="solid"] .theme-dot { display: none; }
    .theme-dot { height: 10px; width: 10px; border-radius: 999px; position: absolute; left: 10px; }
    .theme-dot.primary { top: 10px; background: var(--theme-primary); }
    .theme-dot.accent { top: 24px; background: var(--theme-accent); }
    .theme-swatch { height: 18px; width: 18px; border-radius: 6px; background: linear-gradient(135deg, var(--theme-primary), var(--theme-accent)); box-shadow: 0 4px 10px rgba(0,0,0,.12); }
    .theme-card__content { display: flex; flex-direction: column; gap: 4px; }
    .theme-name { font-weight: 600; color: #111827; }
    .theme-meta { font-size: 12px; color: #6b7280; letter-spacing: .06em; }
    .theme-card:hover { border-color: #c7d2fe; transform: translateY(-2px); box-shadow: 0 12px 25px rgba(15,23,42,.12); }
    .theme-card.is-selected { border-color: #6366f1; box-shadow: 0 10px 24px rgba(79,70,229,.18); }

    /* Invoice subtabs */
    .invoice-tabs { border-bottom: 1px solid #e5e7eb; margin-bottom: 16px; }
    .invoice-tabs .nav-link { border: none; color: #6b7280; font-weight: 600; border-radius: 10px 10px 0 0; padding: 10px 14px; }
    .invoice-tabs .nav-link.active { color: #0f766e; background: rgba(15,118,110,.08); }
    .invoice-tab-content { border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; background: #fff; }
    .invoice-toggle-item { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; margin-bottom: 12px; background: #f8fafc; }
    .invoice-toggle-item .custom-control-label { font-weight: 600; color: #374151; }
    .invoice-toggle-item .custom-control-label i { color: #0f766e; margin-right: 6px; }
    .template-preview-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; }

    /* Timezone */
    .timezone-preview-box { background: linear-gradient(135deg, #1e1b4b, #312e81); border-radius: 12px; padding: 24px; text-align: center; box-shadow: 0 4px 15px rgba(99,102,241,.3); }
    .preview-time { display: flex; flex-direction: column; align-items: center; gap: 8px; }
    .time-display { font-size: 36px; font-weight: 700; color: #fff; font-family: 'Courier New', monospace; letter-spacing: 2px; font-variant-numeric: tabular-nums; }
    .date-display { font-size: 16px; font-weight: 500; color: #a5b4fc; font-family: 'Courier New', monospace; }
    .preview-label { margin-top: 12px; font-size: 12px; color: #c7d2fe; text-transform: uppercase; letter-spacing: 1px; }

    /* Weekend */
    .weekend-grid { display: flex; flex-wrap: wrap; gap: 8px; }
    .weekend-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; border: 1px solid #e5e7eb; background: #f8fafc; font-size: 13px; cursor: pointer; }
    .weekend-pill input { accent-color: #6366f1; }

    /* Modal */
    .modern-modal { border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,.25); }
    .modern-modal-header { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-bottom: none; padding: 20px 24px; }
    .modern-modal-header .modal-title { font-weight: 600; font-size: 18px; }
    .modern-close { color: white; opacity: .8; font-size: 24px; }
    .modern-close:hover { color: white; opacity: 1; }
    .modern-modal-body { padding: 24px; }
    .modern-modal-footer { padding: 20px 24px; border-top: 1px solid #f1f5f9; background: #f8fafc; }
    .preview-content { display: flex; flex-direction: column; gap: 24px; }
    .preview-section { background: #f8fafc; border-radius: 8px; padding: 16px; }
    .preview-title { font-weight: 600; color: #374151; margin-bottom: 12px; font-size: 16px; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px; }
    .preview-item { margin-bottom: 8px; font-size: 14px; color: #6b7280; }
    .preview-item strong { color: #374151; min-width: 120px; display: inline-block; }

    /* Form group label (tenant switcher) */
    .modern-form-group { margin-bottom: 24px; }

    @media (max-width: 768px) {
        .settings-hero { padding: 20px 22px; }
        .hero-title { font-size: 22px; }
        .settings-tab-body { padding: 18px; }
        .form-grid { grid-template-columns: 1fr; gap: 1rem; }
        .form-group-modern.half-width { grid-column: 1; }
        .settings-save-bar { flex-direction: column; }
        .settings-nav-pills { gap: 2px; }
        .settings-nav-pills .nav-link { font-size: 0.8rem; padding: 7px 10px; }
        .time-display { font-size: 26px; }
    }
</style>
@stop

@section('additional_js')
<script>
$(document).ready(function() {
    // Auto-hide alerts
    setTimeout(function() { $('.modern-alert').fadeOut(); }, 5000);

    // Jump to tab containing first validation error
    @if($errors->any())
    (function() {
        const tabMap = {
            'tab-general': ['business_name','email','phone','bin_number','address','bank_details','footer_message','return_policy_days','return_policy_message'],
            'tab-branding': ['logo','theme'],
            'tab-invoice':  [],
            'tab-system':   ['timezone','customer_qr_expiry_days','weekend_days'],
        };
        const errorFields = @json(array_keys($errors->toArray()));
        for (const [tabId, fields] of Object.entries(tabMap)) {
            if (fields.some(f => errorFields.includes(f))) {
                $('[href="#' + tabId + '"]').tab('show');
                break;
            }
        }
    })();
    @endif

    // File upload
    $('#logo').on('change', function() {
        const file = this.files[0];
        if (file) {
            $('.file-upload-text').text(file.name);
            $('.file-upload-subtext').text('File selected');
        } else {
            $('.file-upload-text').text('Choose Logo File');
            $('.file-upload-subtext').text('or drag and drop');
        }
    });

    const fileUploadContainer = $('.file-upload-container');
    fileUploadContainer.on('dragover', function(e) { e.preventDefault(); $(this).addClass('drag-over'); });
    fileUploadContainer.on('dragleave', function(e) { e.preventDefault(); $(this).removeClass('drag-over'); });
    fileUploadContainer.on('drop', function(e) {
        e.preventDefault(); $(this).removeClass('drag-over');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) { $('#logo')[0].files = files; $('#logo').trigger('change'); }
    });

    // Submit loading state
    $('#settingsForm').submit(function() {
        const btn = $('#submit-btn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        return true;
    });

    // Preview modal
    window.previewSettings = function() {
        $('#preview-business-name').text($('#business_name').val() || '-');
        $('#preview-email').text($('#email').val() || '-');
        $('#preview-phone').text($('#phone').val() || '-');
        $('#preview-address').text($('#address').val() || '-');
        $('#preview-return-days').text($('#return_policy_days').val() || '-');
        $('#preview-return-message').text($('#return_policy_message').val() || '-');
        $('#previewModal').modal('show');
    };

    window.resetForm = function() {
        if (confirm('Reset all unsaved changes?')) {
            $('#settingsForm')[0].reset();
            $('.file-upload-text').text('Choose Logo File');
            $('.file-upload-subtext').text('or drag and drop');
        }
    };

    // Timezone preview
    function updateTimezonePreview() {
        const timezone = $('#timezone').val();
        if (!timezone) return;
        try {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { timeZone: timezone, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            const dateStr = now.toLocaleDateString('en-US', { timeZone: timezone, year: 'numeric', month: '2-digit', day: '2-digit' });
            $('.time-display').text(timeStr);
            $('.date-display').text(dateStr);
        } catch(e) {
            $('.time-display').text('--:--:--');
            $('.date-display').text('----/--/--');
        }
    }
    $('#timezone').on('change', updateTimezonePreview);
    setInterval(updateTimezonePreview, 10000);
    updateTimezonePreview();

    // Theme selection
    function syncThemeSelection() {
        const selected = $('input[name="theme"]:checked').closest('.theme-card');
        $('.theme-card').removeClass('is-selected');
        selected.addClass('is-selected');
        const themeName = selected.data('theme-name');
        if (themeName) $('#active-theme-name').text(themeName);
    }
    $('input[name="theme"]').on('change', syncThemeSelection);
    syncThemeSelection();

    // Invoice preview
    const previewBaseUrl = @json($previewInvoiceId ? route('invoices.print', ['invoice' => $previewInvoiceId]) : null);
    const invoicePreviewToggleKeys = ['show_company_phone','show_company_email','show_company_address','show_company_bin','show_bank_details','show_terms','show_footer_message','show_customer_qr','show_signatures'];

    function buildInvoicePreviewUrl(templateOverride) {
        if (!previewBaseUrl) return null;
        const params = new URLSearchParams();
        params.set('preview', '1');
        params.set('template', templateOverride || $('#invoice_template').val() || 'standard');
        invoicePreviewToggleKeys.forEach(key => {
            const cb = document.getElementById(key);
            params.set(key, cb && cb.checked ? '1' : '0');
        });
        const phoneOverride = ($('#invoice_phone_override').val() || '').trim();
        if (phoneOverride) params.set('invoice_phone_override', phoneOverride);
        return `${previewBaseUrl}?${params.toString()}`;
    }

    window.previewInvoiceTemplate = function(templateOverride = null) {
        const url = buildInvoicePreviewUrl(templateOverride);
        if (!url) { alert('Create at least one invoice to preview templates.'); return; }
        window.open(url, '_blank');
    };

    $('.invoice-preview-btn').on('click', function() { window.previewInvoiceTemplate($(this).data('template')); });
    $('#preview-current-template').on('click', function() { window.previewInvoiceTemplate(); });
});
</script>
@stop
