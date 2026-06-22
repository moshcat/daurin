<?php

namespace App\Http\Controllers;

use App\Enums\JenisSampah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ClassifyController extends Controller
{
    /**
     * Label-to-JenisSampah mapping for the waste classifier API.
     * Labels not in this map are unsupported and return jenis_sampah = null.
     */
    private const LABEL_MAP = [
        'plastic'      => JenisSampah::PlastikPet,
        'paper'        => JenisSampah::Kertas,
        'cardboard'    => JenisSampah::Kardus,
        'metal'        => JenisSampah::Logam,
        'brown-glass'  => JenisSampah::Kaca,
        'green-glass'  => JenisSampah::Kaca,
        'white-glass'  => JenisSampah::Kaca,
        'battery'      => JenisSampah::Elektronik,
    ];

    /**
     * Classify an uploaded waste image and return the predicted jenis sampah.
     *
     * @return array{label: string, confidence: float, jenis_sampah: string|null, emoji: string, tip: string, supported: bool}
     */
    public function classify(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $path = $request->file('file')->store('temp-classify', 'local');
        $originalName = $request->file('file')->getClientOriginalName();

        try {
            $result = $this->callVisionApi($path, $originalName);
        } catch (\Throwable) {
            $result = $this->mockClassify($originalName);
        } finally {
            Storage::disk('local')->delete($path);
        }

        return response()->json($result);
    }

    /**
     * Call the waste classifier API (SigLIP2 model) and map its response.
     *
     * @return array{label: string, confidence: float, jenis_sampah: string|null, emoji: string, tip: string, supported: bool}
     *
     * @throws \RuntimeException when the API is unreachable or returns an error.
     */
    private function callVisionApi(string $storagePath, string $originalName): array
    {
        $base = rtrim((string) config('services.waste_classifier.url'), '/');
        $fileContents = Storage::disk('local')->get($storagePath);

        $response = Http::timeout(25)
            ->attach('file', $fileContents, $originalName)
            ->post($base.'/api/classify');

        if (! $response->successful()) {
            throw new \RuntimeException('Waste classifier API returned HTTP '.$response->status());
        }

        $data = $response->json();

        if (empty($data['success']) || empty($data['top'])) {
            throw new \RuntimeException('Unexpected response from waste classifier API.');
        }

        $top = $data['top'];
        $apiLabel = $top['label'] ?? '';
        $jenisSampah = self::LABEL_MAP[$apiLabel] ?? null;

        return [
            'label'        => $top['label_id'] ?? $apiLabel,
            'confidence'   => round((float) ($top['confidence'] ?? 0), 2),
            'jenis_sampah' => $jenisSampah?->value,
            'emoji'        => $top['emoji'] ?? '♻️',
            'tip'          => $top['tip'] ?? '',
            'supported'    => $jenisSampah !== null,
        ];
    }

    /**
     * Return a deterministic-ish mock classification as a fallback when the API is unavailable.
     *
     * @return array{label: string, confidence: float, jenis_sampah: string|null, emoji: string, tip: string, supported: bool}
     */
    private function mockClassify(string $filename): array
    {
        $lower = strtolower($filename);

        /** @var array<string, JenisSampah> */
        $map = [
            'plastik'    => JenisSampah::PlastikPet,
            'pet'        => JenisSampah::PlastikPet,
            'hdpe'       => JenisSampah::PlastikHdpe,
            'kertas'     => JenisSampah::Kertas,
            'kardus'     => JenisSampah::Kardus,
            'logam'      => JenisSampah::Logam,
            'kaleng'     => JenisSampah::Kaleng,
            'kaca'       => JenisSampah::Kaca,
            'elektronik' => JenisSampah::Elektronik,
            'elec'       => JenisSampah::Elektronik,
        ];

        $detected = null;
        foreach ($map as $keyword => $jenis) {
            if (str_contains($lower, $keyword)) {
                $detected = $jenis;
                break;
            }
        }

        if ($detected === null) {
            $cases = JenisSampah::cases();
            $detected = $cases[abs(crc32($filename)) % count($cases)];
        }

        $confidence = round(0.70 + (abs(crc32($filename)) % 25) / 100, 2);

        return [
            'label'        => $detected->label(),
            'confidence'   => $confidence,
            'jenis_sampah' => $detected->value,
            'emoji'        => '♻️',
            'tip'          => 'Klasifikasi dari fallback — hasilnya estimasi.',
            'supported'    => true,
        ];
    }
}
