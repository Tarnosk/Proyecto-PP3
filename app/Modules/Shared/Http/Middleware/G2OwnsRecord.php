<?php

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Auth\G2Identity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Autoriza antes del controlador, sin consumir APIs externas ni modificar datos. */
class G2OwnsRecord
{
    public function handle(Request $request, Closure $next, string $kind)
    {
        [$table, $key, $id] = match ($kind) {
            'entrega' => ['entrega', 'id_entrega', $request->route('id') ?? $request->input('id_entrega')],
            'preparar' => ['entrega', 'id_entrega', $request->route('idEntrega')],
            'remito' => ['remito', 'id_remito', $request->route('id')],
            'rendicion' => ['RENDICION', 'id_rendicion', $request->route('id') ?? $request->input('id_rendicion')],
        };
        $record = DB::table($table)->where($key, (int) $id)->first();
        abort_unless($record, 404);
        $owner = $kind === 'remito'
            ? DB::table('entrega')->where('id_entrega', $record->id_entrega)->value('id_repartidor')
            : $record->id_repartidor;
        abort_unless(G2Identity::role() === 'admin' || (int) $owner === G2Identity::id(), 404);

        return $next($request);
    }
}
