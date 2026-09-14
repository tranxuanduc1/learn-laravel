<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <main>
        <h1>Login</h1>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <p>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
                @error('password')
                    <p>{{ $message }}</p>
                @enderror
            </div>

            <button type="submit">Login</button>
        </form>
    </main>
</body>
</html>
