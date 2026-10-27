<?php

declare(strict_types=1);

namespace App\Modules\Catalogo\Data;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/** Proyección pública: nunca se devuelven usuarios, auditoría ni campos internos de G3. */
final readonly class ProductoCatalogo
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $nombre,
        public string $descripcion,
        public string $categoria,
        public string $categoriaId,
        public string $marca,
        public string $marcaId,
        public ?string $precio,
        public string $presentacion,
        public string $disponibilidad,
        public ?string $imagen,
    ) {}

    public static function fromApi(array $row): self
    {
        $id = $row['id_producto'] ?? $row['id'] ?? null;
        abort_if(isset($row['id'], $row['id_producto']) && (string) $row['id'] !== (string) $row['id_producto'], 502, 'Identificadores de producto inconsistentes.');
        abort_if(Validator::make([...$row, 'id_producto' => $id], [
            'id_producto' => ['required', 'integer', 'min:1'],
            'codigo' => ['required', 'string', 'max:100'],
            'nombre' => ['nullable', 'string', 'max:500'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'stock_disponible' => ['nullable', 'numeric'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'categoria_nombre' => ['nullable', 'string', 'max:300'],
            'marca_nombre' => ['nullable', 'string', 'max:300'],
            'id_categoria' => ['nullable', 'integer', 'min:1'],
            'id_marca' => ['nullable', 'integer', 'min:1'],
            'presentacion_texto' => ['nullable', 'string', 'max:200'],
            'unidad_medida' => ['nullable', 'string', 'max:200'],
            'formato_bulto' => ['nullable', 'string', 'max:100'],
            'unidades_por_bulto' => ['nullable', 'integer', 'min:1'],
        ])->fails(), 502, 'El servicio de productos devolvió información inválida.');

        $nombre = trim($row['nombre'] ?? '') ?: trim($row['descripcion'] ?? '');
        abort_if($nombre === '', 502, 'El producto no tiene un nombre o descripción válidos.');
        $stock = isset($row['stock_disponible']) ? (float) $row['stock_disponible'] : null;
        $minimo = isset($row['stock_minimo']) ? (float) $row['stock_minimo'] : null;
        $disponibilidad = match (true) {
            $stock === null => 'consultar',
            $stock <= 0 => 'sin-stock',
            $minimo !== null && $stock <= $minimo => 'bajo',
            default => 'disponible',
        };
        $presentacion = trim($row['presentacion_texto'] ?? '');
        if ($presentacion === '' && filled($row['formato_bulto'] ?? null) && isset($row['unidades_por_bulto'])) {
            $presentacion = $row['formato_bulto'].' x '.$row['unidades_por_bulto'].' u.';
        }
        $presentacion = $presentacion ?: (trim($row['unidad_medida'] ?? '') ?: 'Presentación a consultar');

        return new self(
            (int) $id, trim($row['codigo']), $nombre, trim($row['descripcion'] ?? ''),
            trim($row['categoria_nombre'] ?? '') ?: 'Otros productos',
            isset($row['id_categoria']) ? (string) $row['id_categoria'] : 'otros',
            trim($row['marca_nombre'] ?? '') ?: 'Sin marca informada',
            isset($row['id_marca']) ? (string) $row['id_marca'] : 'otras',
            isset($row['precio_unitario']) ? number_format((float) $row['precio_unitario'], 2, '.', '') : null,
            $presentacion, $disponibilidad, self::safeImage($row['imagen_url'] ?? $row['imagen'] ?? null),
        );
    }

    public function etiqueta(): string
    {
        return match ($this->disponibilidad) {
            'disponible' => 'Disponible', 'bajo' => 'Últimas unidades',
            'sin-stock' => 'Sin stock', default => 'Consultar disponibilidad',
        };
    }

    public function precioVisible(): string
    {
        return $this->precio === null ? 'Consultar precio' : '$ '.number_format((float) $this->precio, 2, ',', '.');
    }

    public function searchText(): string
    {
        return self::normalize(implode(' ', [$this->codigo, $this->nombre, $this->descripcion, $this->marca, $this->categoria, $this->presentacion]));
    }

    public static function normalize(string $text): string
    {
        $text = Str::lower(Str::ascii($text));
        $text = preg_replace('/(?<=\d),(?=\d)/', '.', $text);
        $text = preg_replace('/(\d)\s*(?:litros?|lts?|l)\b/', '$1l', $text);

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9.]+/', ' ', $text)));
    }

    private static function safeImage(mixed $url): ?string
    {
        if (! is_string($url) || strlen($url) > 2000 || preg_match('/[\x00-\x20\\\\]/', $url)) {
            return null;
        }
        if (preg_match('~^/(?:storage/|assets/catalogo/)[a-zA-Z0-9/_%.\-]+\.(?:png|jpe?g|webp)$~i', $url)
            && ! str_contains(rawurldecode($url), '..')) {
            return $url;
        }
        $parts = parse_url($url);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }

        return in_array(strtolower($parts['host'] ?? ''), config('catalogo.image_hosts', []), true) ? $url : null;
    }
}
