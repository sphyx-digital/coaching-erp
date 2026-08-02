@props(['branches' => collect(), 'courses' => collect(), 'branchId' => null, 'courseId' => null])
<section class="mk-section" id="enquiry" style="background: color-mix(in srgb, var(--brand-hue) 6%, var(--surface));">
    <div class="mk-wrap">
        <h2>Enquire now</h2>
        <p class="lede">Leave your details and our counsellor will get in touch. No spam, ever.</p>

        @if (session('enquiry_ok'))
            <div class="mk-form"><div class="alert alert--success" role="status">Thank you! Your enquiry has been received — we'll call you shortly.</div></div>
        @else
        <form class="mk-form" method="POST" action="{{ url('/site/enquiry') }}">
            @csrf
            <div class="field">
                <label class="field__label" for="name">Full name <span style="color:var(--danger)">*</span></label>
                <input class="input" id="name" name="name" required maxlength="120" value="{{ old('name') }}">
                @error('name')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label class="field__label" for="phone">Mobile number <span style="color:var(--danger)">*</span></label>
                <input class="input" id="phone" name="phone" type="tel" inputmode="numeric" required maxlength="15"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'')" value="{{ old('phone') }}">
                @error('phone')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label class="field__label" for="email">Email</label>
                <input class="input" id="email" name="email" type="email" maxlength="150" value="{{ old('email') }}">
                @error('email')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label class="field__label" for="branch_id">Preferred centre <span style="color:var(--danger)">*</span></label>
                <select class="select" id="branch_id" name="branch_id" required>
                    <option value="">Select a centre</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected(old('branch_id', $branchId) == $b->id)>{{ $b->name }}@if($b->city) — {{ $b->city }}@endif</option>
                    @endforeach
                </select>
                @error('branch_id')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label class="field__label" for="course_id">Interested course</label>
                <select class="select" id="course_id" name="course_id">
                    <option value="">Any / not sure</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}" @selected(old('course_id', $courseId) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="mk-btn mk-btn--primary" type="submit" style="width:100%;justify-content:center;">{{ client_setting('site_cta_label') ?: 'Submit enquiry' }}</button>
        </form>
        @endif
    </div>
</section>
