<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Envía uno o dos mensajes de texto mediante WAHA.
     */
    public function sendMessage(
        string $phoneNumber,
        string $message1,
        ?string $message2 = null
    ): array {
        try {
            $this->validateConfiguration();

            if (trim($message1) === '') {
                return [
                    'success' => false,
                    'message' => 'El mensaje no puede estar vacío.',
                ];
            }

            // Buscar el identificador real del contacto en WAHA.
            $chatId = $this->resolveChatId($phoneNumber);

            // Enviar el primer mensaje.
            $response = $this->sendText($chatId, $message1);

            if (! $response->successful()) {
                return $this->failedResponse(
                    'WAHA no pudo enviar el primer mensaje.',
                    $response,
                    $chatId
                );
            }

            // Enviar el segundo mensaje si fue proporcionado.
            if ($message2 !== null && trim($message2) !== '') {
                $response2 = $this->sendText($chatId, $message2);

                if (! $response2->successful()) {
                    return $this->failedResponse(
                        'El primer mensaje se envió, pero el segundo no pudo enviarse.',
                        $response2,
                        $chatId
                    );
                }
            }

            Log::info('Mensaje enviado mediante WAHA.', [
                'chatId' => $chatId,
            ]);

            return [
                'success' => true,
                'message' => 'WAHA aceptó el envío del mensaje.',
                'chatId' => $chatId,
            ];

        } catch (\Throwable $e) {
            Log::error('Error enviando mensaje mediante WAHA.', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e instanceof \InvalidArgumentException
                    ? $e->getMessage()
                    : 'No fue posible enviar el mensaje. Revisa los logs de Laravel.',
            ];
        }
    }

    /**
     * Envía un archivo cuyo contenido se proporciona como bytes.
     */
    public function sendFileFromBase64(
        string $phoneNumber,
        string $fileContent,
        string $filename,
        string $mimeType = 'application/pdf',
        ?string $caption = null
    ): array {
        try {
            $this->validateConfiguration();

            $chatId = $this->resolveChatId($phoneNumber);

            $payload = [
                'session' => config('services.waha.session'),
                'chatId' => $chatId,
                'file' => [
                    'mimetype' => $mimeType,
                    'filename' => $filename,
                    'data' => base64_encode($fileContent),
                ],
            ];

            if ($caption !== null && trim($caption) !== '') {
                $payload['caption'] = $caption;
            }

            $response = $this->sendFileRequest($payload);

            return $this->processFileResponse(
                $response,
                $chatId,
                $filename
            );

        } catch (\Throwable $e) {
            Log::error('Error enviando archivo Base64 mediante WAHA.', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e instanceof \InvalidArgumentException
                    ? $e->getMessage()
                    : 'No fue posible enviar el archivo. Revisa los logs de Laravel.',
            ];
        }
    }

    /**
     * Envía un archivo desde una URL accesible por WAHA.
     */
    public function sendFileFromUrl(
        string $phoneNumber,
        string $url,
        string $filename,
        string $mimeType = 'application/pdf',
        ?string $caption = null
    ): array {
        try {
            $this->validateConfiguration();

            if (
                ! filter_var($url, FILTER_VALIDATE_URL) ||
                ! in_array(
                    strtolower((string) parse_url($url, PHP_URL_SCHEME)),
                    ['http', 'https'],
                    true
                )
            ) {
                throw new \InvalidArgumentException(
                    'La URL del archivo no es válida.'
                );
            }

            $chatId = $this->resolveChatId($phoneNumber);

            $payload = [
                'session' => config('services.waha.session'),
                'chatId' => $chatId,
                'file' => [
                    'mimetype' => $mimeType,
                    'filename' => $filename,
                    'url' => $url,
                ],
            ];

            if ($caption !== null && trim($caption) !== '') {
                $payload['caption'] = $caption;
            }

            $response = $this->sendFileRequest($payload);

            return $this->processFileResponse(
                $response,
                $chatId,
                $filename
            );

        } catch (\Throwable $e) {
            Log::error('Error enviando archivo mediante URL a WAHA.', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e instanceof \InvalidArgumentException
                    ? $e->getMessage()
                    : 'No fue posible enviar el archivo. Revisa los logs de Laravel.',
            ];
        }
    }

    /**
     * Comprueba que WAHA esté configurado.
     */
    private function validateConfiguration(): void
    {
        if (
            ! config('services.waha.url') ||
            ! config('services.waha.api_key') ||
            ! config('services.waha.session')
        ) {
            throw new \RuntimeException(
                'Falta configurar WAHA_URL, WAHA_API_KEY o WAHA_SESSION en el archivo .env.'
            );
        }
    }

    /**
     * Obtiene el chatId real del contacto.
     *
     * GOWS puede utilizar identificadores @lid en lugar de @c.us.
     * Por eso consultamos a WAHA antes de enviar el mensaje.
     */
    private function resolveChatId(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        // Número mexicano de 10 dígitos.
        if (strlen($digits) === 10) {
            $digits = '52' . $digits;
        } elseif (
            strlen($digits) === 12 &&
            str_starts_with($digits, '52')
        ) {
            // Ya contiene el prefijo mexicano 52.
        } elseif (
            strlen($digits) === 13 &&
            str_starts_with($digits, '521')
        ) {
            // Compatibilidad con el formato antiguo 521.
            $digits = '52' . substr($digits, 3);
        } else {
            throw new \InvalidArgumentException(
                'Escribe un número mexicano válido: 10 dígitos o prefijo 52.'
            );
        }

        $baseUrl = rtrim(config('services.waha.url'), '/');

        $response = Http::timeout(30)
            ->withHeaders([
                'X-Api-Key' => config('services.waha.api_key'),
                'Accept' => 'application/json',
            ])
            ->get(
                $baseUrl . '/api/contacts/check-exists',
                [
                    'phone' => $digits,
                    'session' => config('services.waha.session'),
                ]
            );

        if (! $response->successful()) {
            Log::error('WAHA no pudo consultar el contacto.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException(
                'WAHA no pudo verificar el número. Comprueba la sesión y la clave API.'
            );
        }

        $data = $response->json();

        if (! is_array($data) || empty($data['numberExists'])) {
            throw new \InvalidArgumentException(
                'El número no aparece registrado en WhatsApp o WAHA no pudo encontrarlo.'
            );
        }

        // Preferir el chatId que devuelve WAHA, incluido el formato @lid.
        $chatId = $data['chatId'] ?? $data['pn'] ?? null;

        if (! is_string($chatId) || trim($chatId) === '') {
            throw new \RuntimeException(
                'WAHA encontró el contacto, pero no devolvió un chatId válido.'
            );
        }

        return $chatId;
    }

    /**
     * Envía un mensaje de texto.
     */
    private function sendText(
        string $chatId,
        string $text
    ): Response {
        return Http::timeout(30)
            ->withHeaders([
                'X-Api-Key' => config('services.waha.api_key'),
                'Accept' => 'application/json',
            ])
            ->post(
                rtrim(config('services.waha.url'), '/') . '/api/sendText',
                [
                    'session' => config('services.waha.session'),
                    'chatId' => $chatId,
                    'text' => $text,
                ]
            );
    }

    /**
     * Envía un archivo a WAHA.
     */
    private function sendFileRequest(array $payload): Response
    {
        return Http::timeout(60)
            ->withHeaders([
                'X-Api-Key' => config('services.waha.api_key'),
                'Accept' => 'application/json',
            ])
            ->post(
                rtrim(config('services.waha.url'), '/') . '/api/sendFile',
                $payload
            );
    }

    /**
     * Procesa la respuesta del envío de archivos.
     */
    private function processFileResponse(
        Response $response,
        string $chatId,
        string $filename
    ): array {
        if (! $response->successful()) {
            Log::error('WAHA rechazó el archivo.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'chatId' => $chatId,
                'filename' => $filename,
            ]);

            return [
                'success' => false,
                'message' => 'WAHA no pudo enviar el archivo.',
            ];
        }

        Log::info('Archivo enviado mediante WAHA.', [
            'chatId' => $chatId,
            'filename' => $filename,
            'response' => $response->json(),
        ]);

        return [
            'success' => true,
            'message' => 'WAHA aceptó el envío del archivo.',
            'chatId' => $chatId,
        ];
    }

    /**
     * Registra y devuelve errores HTTP de WAHA.
     */
    private function failedResponse(
        string $message,
        Response $response,
        string $chatId
    ): array {
        Log::error($message, [
            'status' => $response->status(),
            'body' => $response->body(),
            'chatId' => $chatId,
        ]);

        return [
            'success' => false,
            'message' => $message,
        ];
    }
}
