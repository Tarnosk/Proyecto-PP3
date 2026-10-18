@extends('layouts.app')

@section('title', 'Usuarios | Sistema de gestión')

@section('content')
    <section class="page-heading">
        <p class="eyebrow">Administración</p>
        <h1>Usuarios y emails</h1>
        <p>Configure la dirección donde cada usuario recibirá su código de recuperación.</p>
    </section>

    <section class="table-panel" aria-label="Emails de usuarios">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Email de recuperación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->nombre }}</strong>
                                <span>{{ $user->usuario }}</span>
                            </td>
                            <td>{{ $user->rol->value }}</td>
                            <td>{{ $user->estado->value }}</td>
                            <td>
                                <form action="{{ route('users.email.update', $user) }}" method="post" class="email-form">
                                    @csrf
                                    @method('PUT')
                                    <label class="sr-only" for="email-{{ $user->id }}">Email de {{ $user->usuario }}</label>
                                    <input type="email" id="email-{{ $user->id }}" name="email" maxlength="160" required value="{{ $user->email }}" placeholder="usuario@dominio.com">
                                    <button type="submit" class="button button-primary button-small">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
