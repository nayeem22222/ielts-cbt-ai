<x-layouts.guest title="Student Registration">
<div class="auth-page">
    <form class="auth-card" method="POST" action="{{ route('register.store') }}">
        @csrf
        <h1>Create account</h1>
        <p>Register and start free IELTS CBT practice.</p>
        <div class="field"><label>Full name</label><input name="name" type="text" value="{{ old('name') }}" required placeholder="Your full name"></div>
        <div class="field"><label>Email</label><input name="email" type="email" value="{{ old('email') }}" required placeholder="student@example.com"></div>
        <div class="field"><label>Password</label><input name="password" type="password" required placeholder="Minimum 8 characters"></div>
        <div class="field"><label>Confirm Password</label><input name="password_confirmation" type="password" required placeholder="Repeat password"></div>
        <div class="auth-meta"><label><input type="checkbox" required> I agree to the terms</label></div>
        <button class="btn-orange" type="submit" style="width:100%;justify-content:center">Create Student Account</button>
        <p style="margin-top:20px;text-align:center">Already have an account? <a class="auth-link" href="/login">Login</a></p>
    </form>
</div>
</x-layouts.guest>
