<?php

namespace App\Telegram\Menu;

/**
 * Construye las pantallas del menú guiado (texto + teclado inline).
 *
 * callback_data:
 *   m:nav:<pantalla>  → navega a esa pantalla (edita el mensaje)
 *   m:run:<clave>     → ejecuta una acción rápida (ver MenuCallbackHandler)
 */
final class Menu
{
    /**
     * @return array{text: string, keyboard: array<int, array<int, array{text: string, callback_data: string}>>}
     */
    public function screen(string $key): array
    {
        return match ($key) {
            'gasto' => [
                'text' => "💸 Gastos\n\n"
                    . "Registrar uno:\n/gasto <importe> <categoría> [fecha] [descripción]\n\n"
                    . "Ejemplos:\n• /gasto 12,50 Comida menú\n• /gasto 20 Comida ayer  (fecha pasada)\n\n"
                    . "💡 Si no pones categoría, te la pregunto con botones.\n"
                    . '💡 También puedes mandarme una foto del ticket 📸',
                'keyboard' => [
                    [self::b('📋 Ver gastos', 'm:run:ultimos')],
                    [self::b('🧾 Enviar ticket', 'm:nav:ticket'), self::b('🔁 Gastos fijos', 'm:nav:fijos')],
                    self::back(),
                ],
            ],
            'ingreso' => [
                'text' => "💰 Ingresos\n\n"
                    . "Registrar uno:\n/ingreso <importe> [descripción]\n\n"
                    . 'Ejemplo:  /ingreso 1800 nómina',
                'keyboard' => [
                    [self::b('📋 Ver ingresos', 'm:run:ingresos')],
                    [self::b('🔁 Ingresos fijos', 'm:nav:fijosingreso')],
                    self::back(),
                ],
            ],
            'ticket' => [
                'text' => "🧾 Enviar un ticket\n\n"
                    . 'Mándame una foto de un ticket y leeré el importe, la fecha y la categoría '
                    . 'automáticamente. Te pediré confirmación antes de guardarlo.',
                'keyboard' => [self::back()],
            ],
            'graf' => [
                'text' => "📈 Gráficos\n\n¿Cuál quieres ver?",
                'keyboard' => [
                    [self::b('🥧 Reparto', 'm:run:gcat'), self::b('📊 Gastado/límite', 'm:run:glim')],
                    [self::b('📈 Evolución', 'm:run:gevo')],
                    self::back(),
                ],
            ],
            'cats' => [
                'text' => "🏷️ Categorías\n\nCrear una nueva:\n/nuevacategoria <nombre>",
                'keyboard' => [
                    [self::b('📋 Ver categorías', 'm:run:cats')],
                    self::back(),
                ],
            ],
            'limits' => [
                'text' => "🎯 Límites mensuales\n\n"
                    . "Fijar el máximo de una categoría:\n/limite <categoría> <importe>\n"
                    . 'Ej:  /limite Comida 300',
                'keyboard' => [
                    [self::b('📋 Ver límites', 'm:run:limits')],
                    self::back(),
                ],
            ],
            'fijos' => [
                'text' => "🔁 Gastos fijos\n\n"
                    . "Crear uno:\n/recurrente <día> <importe> <categoría> [descripción]\n"
                    . '💡 Día -1 = último día del mes.',
                'keyboard' => [
                    [self::b('📋 Ver fijos', 'm:run:fijos')],
                    self::back(),
                ],
            ],
            'fijosingreso' => [
                'text' => "🔁 Ingresos fijos\n\n"
                    . "Crear uno:\n/ingresofijo <día> <importe> [descripción]\n"
                    . '💡 Día -1 = último día del mes.',
                'keyboard' => [
                    [self::b('📋 Ver fijos', 'm:run:fijosingreso')],
                    self::back(),
                ],
            ],
            default => [
                'text' => "👋 ¿Qué quieres hacer?\n\nElige una opción 👇",
                'keyboard' => [
                    [self::b('💸 Gastos', 'm:nav:gasto'), self::b('💰 Ingresos', 'm:nav:ingreso')],
                    [self::b('📊 Resumen', 'm:run:resumen'), self::b('📈 Gráficos', 'm:nav:graf')],
                    [self::b('🏷️ Categorías', 'm:nav:cats'), self::b('🎯 Límites', 'm:nav:limits')],
                    [self::b('📄 Excel del mes', 'm:run:excel')],
                ],
            ],
        };
    }

    /**
     * @return array{text: string, callback_data: string}
     */
    private static function b(string $text, string $data): array
    {
        return ['text' => $text, 'callback_data' => $data];
    }

    /**
     * @return array<int, array{text: string, callback_data: string}>
     */
    private static function back(): array
    {
        return [self::b('⬅️ Menú', 'm:nav:main')];
    }
}
