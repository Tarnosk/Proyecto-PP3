@if ($errors->any())
    <div class="alert alert-error" role="alert">
        @foreach (collect($errors->all())->unique() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success" role="status">
        <p>{{ session('success') }}</p>
    </div>
@endif
