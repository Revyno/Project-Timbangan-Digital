<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Timbangan Digital IoT API",
 *     description="API untuk integrasi perangkat timbangan IoT (Arduino/ESP8266) dengan sistem penimbangan digital. Semua endpoint menggunakan token perangkat untuk autentikasi.",
 *     @OA\Contact(
 *         name="Timbangan Digital Team"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description=" Development Server"
 * )
 *
 * @OA\Tag(
 *     name="FG Pasuruan",
 *     description="Endpoint untuk timbangan Formulasi Pasuruan (FG)"
 * )
 * @OA\Tag(
 *     name="FG PSN",
 *     description="Endpoint untuk timbangan Finished Goods Pasuruan (FG PSN)"
 * )
 * @OA\Tag(
 *     name="FG Surabaya",
 *     description="Endpoint untuk timbangan Formulasi Surabaya"
 * )
 * @OA\Tag(
 *     name="CS Noodle Surabaya",
 *     description="Endpoint untuk timbangan CS Noodle Surabaya"
 * )
 * @OA\Tag(
 *     name="CS FG Surabaya",
 *     description="Endpoint untuk timbangan CS FG Surabaya"
 * )
 * @OA\Tag(
 *     name="Incoming Singkong",
 *     description="Endpoint untuk timbangan Incoming Singkong Pasuruan"
 * )
 * @OA\Tag(
 *     name="Incoming RMPM",
 *     description="Endpoint untuk timbangan Incoming RMPM"
 * )
 * @OA\Tag(
 *     name="Driver",
 *     description="Identifikasi driver via QR Code"
 * )
 * @OA\Tag(
 *     name="System",
 *     description="Health check dan status sistem"
 * )
 *
 * @OA\Schema(
 *     schema="WeightRequest",
 *     type="object",
 *     required={"token", "weight"},
 *     @OA\Property(property="token", type="string", example="DEV-TOKEN-12345", description="Token unik perangkat timbangan"),
 *     @OA\Property(property="weight", type="number", format="float", example=25.5, description="Berat timbangan dalam kilogram")
 * )
 *
 * @OA\Schema(
 *     schema="SettingsResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", enum={"ready", "idle"}, example="ready"),
 *     @OA\Property(property="kode_produksi", type="string", example="KP-2024-001"),
 *     @OA\Property(property="nama_produk", type="string", example="Mie Goreng 85g"),
 *     @OA\Property(property="operator", type="string", example="Budi Santoso"),
 *     @OA\Property(property="expired", type="string", example="2025-12-31")
 * )
 *
 * @OA\Schema(
 *     schema="WeightSuccessResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", example="Data berat 25.5 kg tersimpan"),
 *     @OA\Property(property="record_id", type="integer", example=42)
 * )
 *
 * @OA\Schema(
 *     schema="PingRequest",
 *     type="object",
 *     required={"token"},
 *     @OA\Property(property="token", type="string", example="DEV-TOKEN-12345", description="Token unik perangkat timbangan")
 * )
 *
 * @OA\Schema(
 *     schema="PingResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="ok"),
 *     @OA\Property(property="server_time", type="string", example="2024-01-15 08:30:00")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="Unauthorized")
 * )
 */
class SwaggerInfoController extends Controller
{
    // Kelas ini hanya sebagai tempat anotasi global Swagger
}
