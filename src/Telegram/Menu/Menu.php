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
                    . "📸 También puedes mandarme una foto del ticket.\n"
                    . '🎙️ …o un audio: «me he gastado 12 con 50 en gasolina».',
                'keyboard' => [
                    [self::b('📋 Ver gastos', 'm:run:ultimos')],
                    [self::b('🧾 Enviar ticket', 'm:nav:ticket'), self::b('🔁 Gastos fijos', 'm:nav:fijos')],
                    self::back(),
                ],
            ],
            'ingreso' => [
                'text' => "💰 Ingresos\n\n"
                    . "Registrar uno:\n/ingreso <importe> [descripción]\n\n"
                    . "Ejemplo:  /ingreso 1800 nómina\n\n"
                    . '🎙️ …o mándame un audio: «hoy he cobrado la nómina, 1800».',
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
            'ayuda' => [
                'text' => self::helpText(),
                'keyboard' => [self::back()],
            ],
            default => [
                'text' => "👋 ¿Qué quieres hacer?\n\nElige una opción 👇",
                'keyboard' => [
                    [self::b('💸 Gastos', 'm:nav:gasto'), self::b('💰 Ingresos', 'm:nav:ingreso')],
                    [self::b('📊 Resumen', 'm:run:resumen'), self::b('📈 Gráficos', 'm:nav:graf')],
                    [self::b('🏷️ Categorías', 'm:nav:cats'), self::b('🎯 Límites', 'm:nav:limits')],
                    [self::b('📄 Excel del mes', 'm:run:excel'), self::b('❓ Ayuda', 'm:nav:ayuda')],
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

    /** Resumen amplio y en lenguaje sencillo de todo lo que sabe hacer el bot. */
    private static function helpText(): string
    {
        return "❓ Guía rápida\n\n"
            . "Soy tu ayudante para llevar las cuentas del mes. Puedes hablarme de tres formas:\n\n"

            . "🎙️ Por voz (lo más cómodo)\n"
            . "Mándame un audio contándome el movimiento y yo lo apunto. Entiendo si es gasto o ingreso, el importe, la fecha y de qué es. Ejemplos:\n"
            . "• «Me he gastado 12 con 50 en gasolina»\n"
            . "• «Ayer 30 euros en la compra»\n"
            . "• «Hoy he cobrado la nómina, 1800»\n"
            . "Siempre te pido confirmación antes de guardar.\n\n"

            . "📸 Por foto de ticket\n"
            . "Hazle una foto al ticket y leo el importe, la fecha y el comercio, y te propongo la categoría. Tú confirmas.\n\n"

            . "⌨️ Por texto\n"
            . "• Gasto:  /gasto 20 Comida  (puedes añadir fecha como «ayer» y una nota)\n"
            . "• Ingreso:  /ingreso 1800 nómina\n"
            . "Si no pones categoría en un gasto, te la pregunto con botones.\n\n"

            . "📋 Ver, editar y borrar\n"
            . "Desde «💸 Gastos» o «💰 Ingresos» pulsa «Ver» y toca cualquier movimiento para borrarlo o (en gastos) cambiarle la categoría.\n\n"

            . "🔁 Movimientos fijos\n"
            . "Cosas que se repiten cada mes (alquiler, nómina, suscripciones) se apuntan solas el día que toca. Las creas en «Gastos → Gastos fijos» e «Ingresos → Ingresos fijos».\n\n"

            . "🏷️ Categorías y 🎯 Límites\n"
            . "Organiza tus gastos por categorías y ponles un tope al mes. Si te acercas o pasas del límite, te aviso al momento.\n\n"

            . "📊 Resumen · 📈 Gráficos · 📄 Excel\n"
            . "Consulta cómo va el mes (ingresado − gastado), míralo en gráficos o descárgate todo en una hoja de Excel.\n\n"

            . "💡 Escribe /menu en cualquier momento para volver aquí.";
    }
}
