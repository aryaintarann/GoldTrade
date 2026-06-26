# Product Requirements Document
# GoldAI Scalper — XAUUSD Signal Trading Chatbot

**Versi:** 3.0.0
**Tanggal:** 26 Juni 2026
**Status:** Draft Final
**Stack:** Laravel 13.x (Backend API + AI Agent) · Next.js 16.x (Frontend)
**Pemilik Produk:** Internal — Teridox

> **Catatan penting soal akurasi:** Tidak ada sistem analisa teknikal maupun AI yang dapat menjamin prediksi pasar yang selalu benar. Produk ini dirancang sebagai *decision-support tool* dengan metodologi yang transparan dan terukur (confluence indikator, confidence score, win-rate tercatat) — bukan sebagai sistem yang mengklaim "selalu akurat". Seluruh fitur di bawah didesain mengikuti prinsip ini.

---

## Daftar Isi

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Tujuan & Sasaran](#2-tujuan--sasaran)
3. [Ruang Lingkup](#3-ruang-lingkup)
4. [Persona Pengguna](#4-persona-pengguna)
5. [Arsitektur Sistem](#5-arsitektur-sistem)
6. [Tech Stack](#6-tech-stack)
7. [Struktur Database (Laravel)](#7-struktur-database-laravel)
8. [AI Agent & Tools (Laravel AI SDK)](#8-ai-agent--tools-laravel-ai-sdk)
9. [API Routes & Autentikasi](#9-api-routes--autentikasi)
10. [Realtime Layer (Reverb)](#10-realtime-layer-reverb)
11. [Integrasi Data Eksternal](#11-integrasi-data-eksternal)
12. [Kalkulasi Indikator Teknikal](#12-kalkulasi-indikator-teknikal)
13. [Sistem Signal & Confidence Scoring](#13-sistem-signal--confidence-scoring)
14. [Frontend Next.js — Struktur & Integrasi Chat](#14-frontend-nextjs--struktur--integrasi-chat)
15. [Spesifikasi UI/UX](#15-spesifikasi-uiux)
16. [PWA Specification](#16-pwa-specification)
17. [Risk Management Tools](#17-risk-management-tools)
18. [Signal Journal & Performance Tracking](#18-signal-journal--performance-tracking)
19. [Environment Variables](#19-environment-variables)
20. [Keamanan & Privasi](#20-keamanan--privasi)
21. [Penanganan Error & Fallback](#21-penanganan-error--fallback)
22. [Kriteria Penerimaan](#22-kriteria-penerimaan)
23. [Roadmap Pengembangan](#23-roadmap-pengembangan)
24. [Referensi & Dokumentasi](#24-referensi--dokumentasi)

---

## 1. Ringkasan Eksekutif

GoldAI Scalper adalah aplikasi web berbasis chatbot yang memberikan analisa dan signal trading XAUUSD (Gold/USD) menggunakan kombinasi analisa teknikal multi-timeframe dan reasoning AI. Arsitektur menggunakan **Laravel sebagai backend API dan AI orchestration layer**, serta **Next.js sebagai frontend chat interface**, terhubung melalui protokol streaming yang kompatibel satu sama lain (Vercel AI Data Protocol), sehingga kedua framework bisa saling terintegrasi secara native tanpa lapisan adapter tambahan.

Perubahan utama dari versi sebelumnya (v2.1, Next.js + Supabase): backend dipindahkan ke Laravel untuk memanfaatkan **Laravel AI SDK** (`laravel/ai`) yang menyediakan abstraksi Agent + Tools + Structured Output secara native — cocok untuk use case ini karena AI butuh memanggil beberapa "alat" (fetch data candle, hitung indikator, cek berita) sebelum menyusun signal final.

## 2. Tujuan & Sasaran

- Memberikan analisa XAUUSD multi-timeframe yang konsisten metodologinya (bukan sekadar "tebakan" AI).
- Menyajikan signal dalam format terstruktur (arah, entry, SL/TP, confidence) agar bisa ditampilkan sebagai card UI, bukan blok teks panjang.
- Mencatat performa signal dari waktu ke waktu agar klaim akurasi bisa dibuktikan dengan data historis riil.
- Membangun arsitektur yang reusable: Laravel API ini nantinya juga bisa dikonsumsi oleh client lain (bot Telegram, mobile app Flutter) tanpa perubahan besar.

## 3. Ruang Lingkup

**Termasuk (v3.0):**
- Chat interface realtime dengan streaming response.
- Analisa multi-timeframe (M1–H4) dengan 6 indikator inti.
- AI agent dengan tools untuk fetch data & kalkulasi (bukan AI menghitung manual dari teks).
- Signal terstruktur + journal + dashboard win-rate.
- Realtime price ticker via WebSocket (Reverb).
- PWA untuk akses mobile-like.

**Tidak termasuk (v3.0):** auto-trading/eksekusi order ke broker, multi-instrumen selain XAUUSD, mobile app native (akan jadi fase terpisah jika dibutuhkan).

## 4. Persona Pengguna

| Persona | Kebutuhan |
|---|---|
| Scalper harian | Signal cepat di timeframe M1–M15, respons instan |
| Swing trader | Konteks H1–H4 lebih dominan, lebih sabar terhadap entry |
| Trader baru | Penjelasan alasan teknikal yang mudah dipahami, bukan jargon mentah |

## 5. Arsitektur Sistem

Arsitektur **decoupled** dua layanan terpisah yang berkomunikasi via HTTP/SSE dan WebSocket:

```
┌─────────────────────────┐         HTTPS (SSE Stream,            ┌──────────────────────────┐
│   Next.js 16 (Vercel)    │◄───────  Vercel AI Data Protocol) ───►│  Laravel 13 API (Forge/   │
│                          │                                        │  Railway/VPS)              │
│  - Chat UI (useChat)     │         WebSocket (Reverb)             │  - AI Agent (laravel/ai)   │
│  - TradingView Widget    │◄───────────────────────────────────────│  - Tools (data, indikator) │
│  - Dashboard journal     │                                        │  - Sanctum (SPA auth)      │
│  - PWA shell             │         Cookie-based session            │  - Queue Jobs (Horizon)    │
└─────────────────────────┘         (Sanctum stateful domain)        └─────────────┬─────────────┘
                                                                                     │
                                                            ┌────────────────────────┼────────────────────────┐
                                                            ▼                        ▼                        ▼
                                                   OHLC Data API           Economic Calendar API      MySQL/Postgres
                                                  (multi-timeframe)         (news high-impact)         (signals, users,
                                                                                                          journal, cache)
```

**Alasan keputusan arsitektur** (dibahas di sesi sebelumnya): Vercel tidak dapat menjalankan PHP, sehingga Laravel wajib di-deploy di layanan terpisah (Laravel Cloud, Forge + VPS, atau Railway), sementara Next.js tetap di Vercel. Integrasi antar keduanya menjadi mulus karena Laravel AI SDK punya method `usingVercelDataProtocol()` yang outputnya langsung kompatibel dengan hook `useChat` di sisi Next.js — tidak perlu menulis parser SSE custom.

## 6. Tech Stack

| Layer | Teknologi | Keterangan |
|---|---|---|
| Backend Framework | Laravel 13.x | API + AI orchestration |
| AI Orchestration | `laravel/ai` (Laravel AI SDK) | Agent, Tools, Structured Output |
| Auth | Laravel Sanctum (mode SPA/stateful) | Cookie-based, cocok untuk frontend first-party |
| Realtime | Laravel Reverb | WebSocket native Laravel, untuk price ticker |
| Queue | Laravel Horizon + Redis | Job async: fetch data, broadcast |
| Database | MySQL atau PostgreSQL | Signal, journal, cache |
| Frontend Framework | Next.js 16.x (App Router) | Chat UI, dashboard |
| AI Frontend SDK | Vercel AI SDK (`@ai-sdk/react`, `useChat`) | Konsumsi stream dari Laravel |
| Chart Visual | TradingView Advanced Chart Widget | Tampilan chart, bukan sumber data |
| Styling | Tailwind CSS | Konsisten dengan project Next.js kamu sebelumnya |
| Deployment Backend | Laravel Cloud / Forge + VPS / Railway | Vercel tidak bisa jalankan PHP |
| Deployment Frontend | Vercel | Sesuai stack yang biasa kamu pakai |

## 7. Struktur Database (Laravel)

Migrasi inti (disederhanakan):

```php
// users
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->enum('tier', ['free', 'premium'])->default('free');
    $table->timestamps();
});

// signals — hasil output AI agent, tersimpan terstruktur
Schema::create('signals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained();
    $table->enum('direction', ['BUY', 'SELL', 'WAIT']);
    $table->decimal('entry_price', 10, 2)->nullable();
    $table->decimal('stop_loss', 10, 2)->nullable();
    $table->json('take_profits')->nullable(); // multi-TP
    $table->unsignedTinyInteger('confidence'); // 0-100
    $table->text('reasoning');
    $table->json('indicators_snapshot'); // nilai indikator saat signal dibuat
    $table->json('timeframes_used');
    $table->enum('outcome', ['pending', 'tp_hit', 'sl_hit', 'expired'])->default('pending');
    $table->timestamps();
});

// market_data_cache — cache OHLC per timeframe untuk kurangi hit API eksternal
Schema::create('market_data_cache', function (Blueprint $table) {
    $table->id();
    $table->string('timeframe'); // M1, M5, M15, H1, H4
    $table->json('candles');
    $table->timestamp('fetched_at');
});

// news_events — economic calendar
Schema::create('news_events', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->enum('impact', ['low', 'medium', 'high']);
    $table->timestamp('event_time');
    $table->string('currency'); // USD, dll
});
```

## 8. AI Agent & Tools (Laravel AI SDK)

Inti dari sistem ini adalah satu **Agent** dengan beberapa **Tools** yang bisa dipanggil sendiri oleh AI saat reasoning — bukan kita yang manual menyusun prompt berisi semua data sekaligus. Ini membuat AI bisa "memutuskan" data apa yang perlu di-fetch sesuai pertanyaan user.

```php
// app/Ai/Agents/XAUUSDAnalystAgent.php
namespace App\Ai\Agents;

use App\Ai\Tools\FetchOHLCDataTool;
use App\Ai\Tools\CalculateIndicatorsTool;
use App\Ai\Tools\DetectSupportResistanceTool;
use App\Ai\Tools\FetchEconomicCalendarTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class XAUUSDAnalystAgent implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable;

    public function __construct(public ?int $userId = null) {}

    public function instructions(): Stringable|string
    {
        return <<<TEXT
        Kamu adalah analis trading XAUUSD profesional. Gunakan tools yang tersedia untuk
        mengambil data candle, menghitung indikator, mendeteksi support/resistance, dan
        memeriksa kalender berita sebelum menyusun signal. Jangan mengarang harga atau
        nilai indikator — selalu panggil tool terkait. Jika confluence indikator lemah
        atau berlawanan arah, keluarkan signal WAIT, jangan dipaksakan BUY/SELL.
        TEXT;
    }

    public function tools(): iterable
    {
        return [
            new FetchOHLCDataTool,
            new CalculateIndicatorsTool,
            new DetectSupportResistanceTool,
            new FetchEconomicCalendarTool,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'direction' => $schema->string()->enum(['BUY', 'SELL', 'WAIT'])->required(),
            'entry_price' => $schema->number()->nullable(),
            'stop_loss' => $schema->number()->nullable(),
            'take_profits' => $schema->array()->items($schema->number())->nullable(),
            'confidence' => $schema->integer()->min(0)->max(100)->required(),
            'reasoning' => $schema->string()->required(),
        ];
    }
}
```

Contoh salah satu Tool — agent yang memutuskan kapan tool ini dipanggil, bukan kode kita yang hardcode urutannya:

```php
// app/Ai/Tools/CalculateIndicatorsTool.php
namespace App\Ai\Tools;

use App\Services\TechnicalIndicatorService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CalculateIndicatorsTool implements Tool
{
    public function __construct(private TechnicalIndicatorService $service) {}

    public function description(): Stringable|string
    {
        return 'Menghitung RSI, MACD, EMA, Bollinger Bands, ATR, dan Stochastic untuk timeframe tertentu pada XAUUSD.';
    }

    public function handle(Request $request): Stringable|string
    {
        $result = $this->service->calculate(
            timeframe: $request['timeframe'],
        );

        return json_encode($result);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'timeframe' => $schema->string()->enum(['M1', 'M5', 'M15', 'H1', 'H4'])->required(),
        ];
    }
}
```

Tools lain (`FetchOHLCDataTool`, `DetectSupportResistanceTool`, `FetchEconomicCalendarTool`) mengikuti pola yang sama — masing-masing membungkus satu service spesifik agar AI bisa memanggilnya secara independen.

## 9. API Routes & Autentikasi

Autentikasi menggunakan **Sanctum mode SPA (stateful, cookie-based)** karena Next.js dianggap first-party frontend, bukan third-party API consumer — ini lebih aman daripada bearer token yang tersimpan di localStorage (rawan XSS).

```php
// config/sanctum.php
'stateful' => [
    'localhost:3000',
    'goldai-scalper.vercel.app', // domain production Next.js
],
```

```php
// routes/web.php — login & csrf cookie (dipanggil dari Next.js sebelum request pertama)
Route::post('/login', [AuthController::class, 'login']);

// routes/api.php — dilindungi middleware stateful + sanctum
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/chat', [ChatController::class, 'stream']); // streaming endpoint utama
    Route::get('/signals', [SignalController::class, 'index']); // riwayat signal
    Route::get('/signals/performance', [SignalController::class, 'performance']); // win-rate
});
```

```php
// app/Http/Controllers/ChatController.php
public function stream(Request $request)
{
    return (new XAUUSDAnalystAgent($request->user()?->id))
        ->stream($request->input('message'))
        ->usingVercelDataProtocol();
}
```

Alur login dari sisi Next.js: ambil CSRF cookie dulu (`/sanctum/csrf-cookie`), lalu POST `/login` dengan `credentials: 'include'`, baru setelah itu semua request ke `/api/*` otomatis terautentikasi via cookie session — tanpa perlu menyimpan token manual.

## 10. Realtime Layer (Reverb)

Reverb digunakan khusus untuk **price ticker live** dan **notifikasi signal baru** — bukan untuk chat (chat sudah streaming via SSE di section 9).

```php
// app/Events/GoldPriceUpdated.php
class GoldPriceUpdated implements ShouldBroadcast
{
    public function __construct(public float $price, public float $changePercent) {}

    public function broadcastOn(): Channel
    {
        return new Channel('xauusd-price');
    }
}
```

Di sisi Next.js, subscribe channel ini pakai **Laravel Echo** untuk menampilkan harga live di sidebar tanpa polling.

## 11. Integrasi Data Eksternal

| Kebutuhan | Sumber | Catatan |
|---|---|---|
| OHLC multi-timeframe | API penyedia data forex/gold (mis. API Ninjas, atau provider serupa) | Di-cache di `market_data_cache`, fetch ulang tiap interval sesuai timeframe |
| Spot price realtime | API gold spot price | Dipoll job berkala, hasil di-broadcast via Reverb |
| Visual chart | TradingView Advanced Chart Widget | Embed only, bukan sumber data numerik (tidak ada akses programatik resmi tanpa lisensi) |
| Economic calendar | Provider kalender ekonomi forex | Difilter untuk event berdampak USD/Gold (NFP, FOMC, CPI) |

## 12. Kalkulasi Indikator Teknikal

Logika kalkulasi tetap sama seperti PRD sebelumnya, dipindahkan ke `TechnicalIndicatorService` di Laravel:

- **RSI (14)** — rata-rata gain/loss 14 periode.
- **MACD (12, 26, 9)** — EMA12 − EMA26, signal line EMA9 dari MACD line.
- **EMA (9/21/50/200)** — untuk trend bias multi-timeframe.
- **Bollinger Bands (20, 2σ)** — deteksi overbought/oversold relatif volatilitas.
- **ATR (14)** — dasar penentuan jarak SL yang proporsional terhadap volatilitas saat ini.
- **Stochastic (14,3,3)** — konfirmasi momentum tambahan.

## 13. Sistem Signal & Confidence Scoring

Confidence dihitung dari **confluence** — makin banyak indikator + struktur S/R + bias multi-timeframe yang searah, makin tinggi skornya. Bobot indikatif:

| Komponen | Bobot |
|---|---|
| Trend EMA multi-timeframe searah | 25% |
| RSI + Stochastic searah (tidak divergen) | 20% |
| MACD crossover mendukung arah | 20% |
| Posisi terhadap S/R & Bollinger Band | 20% |
| Tidak ada news high-impact dalam 2 jam ke depan | 15% |

Skor < 50% → signal otomatis **WAIT**, bukan dipaksakan BUY/SELL. Ini mencegah bot "asal kasih sinyal".

## 14. Frontend Next.js — Struktur & Integrasi Chat

```
app/
  (chat)/page.tsx          # halaman utama chat + chart
  dashboard/page.tsx       # journal & win-rate
  layout.tsx
components/
  ChatPanel.tsx
  SignalCard.tsx
  PriceTicker.tsx          # subscribe Reverb via Laravel Echo
  TradingViewWidget.tsx
lib/
  echo.ts                  # setup Laravel Echo client
```

Integrasi chat ke backend Laravel:

```tsx
// components/ChatPanel.tsx
import { useChat } from '@ai-sdk/react';
import { DefaultChatTransport } from 'ai';

const { messages, sendMessage } = useChat({
  transport: new DefaultChatTransport({
    api: `${process.env.NEXT_PUBLIC_API_URL}/api/chat`,
    credentials: 'include', // wajib, karena auth pakai cookie Sanctum
  }),
});
```

Karena Laravel agent sudah men-stream dengan `usingVercelDataProtocol()`, tidak ada parsing manual SSE yang dibutuhkan di sisi Next.js — `useChat` langsung mengenali format event-nya.

## 15. Spesifikasi UI/UX

Layout tiga kolom (mengikuti desain GoldAI Scalper sebelumnya, tetap dipertahankan):

- **Kiri (sidebar):** indikator realtime per timeframe, signal strength meter, news impact list.
- **Tengah:** chat area dengan signal banner besar di atas (arah, confidence, pair), riwayat percakapan, quick-action buttons.
- **Kanan (opsional/desktop):** TradingView chart widget untuk konfirmasi visual.

Signal selalu dirender sebagai **card terstruktur** di dalam bubble chat (bukan teks panjang), berisi: badge arah berwarna, entry/SL/TP, confidence bar, dan accordion "lihat alasan teknikal".

## 16. PWA Specification

- `manifest.json` dengan ikon adaptif, `display: standalone`.
- Service worker untuk caching shell UI (bukan data realtime — data tetap harus fresh dari server).
- Push notification opsional untuk signal baru (lewat Web Push, terpisah dari Reverb).

## 17. Risk Management Tools

- Position size calculator: input balance + risk % + jarak SL (dari ATR) → output lot size otomatis.
- Risk-reward ratio ditampilkan eksplisit sebelum user mengambil keputusan, dihitung dari entry/SL/TP yang dikeluarkan agent.

## 18. Signal Journal & Performance Tracking

- Setiap row di tabel `signals` di-update outcome-nya via scheduled job yang mengecek apakah harga sudah menyentuh TP/SL.
- Dashboard menampilkan win-rate riil per periode, per arah (BUY vs SELL), dan per range confidence score — supaya transparan apakah confidence score yang tinggi memang berkorelasi dengan hasil yang lebih baik.

## 19. Environment Variables

```ini
# Laravel (.env)
APP_URL=https://api.goldaiscalper.com
SANCTUM_STATEFUL_DOMAINS=localhost:3000,goldai-scalper.vercel.app
SESSION_DOMAIN=.goldaiscalper.com

OPENROUTER_API_KEY=
# atau provider lain yang didukung laravel/ai (ANTHROPIC_API_KEY, OPENAI_API_KEY, dst.)

REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=

DB_CONNECTION=mysql
QUEUE_CONNECTION=redis

OHLC_DATA_API_KEY=
ECONOMIC_CALENDAR_API_KEY=
```

```ini
# Next.js (.env.local)
NEXT_PUBLIC_API_URL=https://api.goldaiscalper.com
NEXT_PUBLIC_REVERB_KEY=
NEXT_PUBLIC_REVERB_HOST=
NEXT_PUBLIC_TRADINGVIEW_SYMBOL=OANDA:XAUUSD
```

## 20. Keamanan & Privasi

- CORS dikonfigurasi ketat hanya mengizinkan domain frontend resmi.
- Sanctum stateful + CSRF protection mencegah session hijacking dari domain lain.
- Rate limiting per user pada endpoint `/api/chat` untuk mencegah biaya AI API membengkak akibat spam request.
- API key provider AI & data eksternal hanya disimpan di backend Laravel, tidak pernah terekspos ke frontend.

## 21. Penanganan Error & Fallback

| Skenario | Penanganan |
|---|---|
| API data OHLC down | Pakai cache terakhir dari `market_data_cache`, beri notice "data mungkin tidak realtime" |
| AI provider timeout/limit | Fallback ke model kedua (konfigurasi multi-provider di `laravel/ai`) |
| Reverb connection putus | Auto-reconnect di Laravel Echo, fallback polling sementara |

## 22. Kriteria Penerimaan

- User dapat memulai chat dan menerima signal terstruktur dalam <10 detik untuk kondisi normal.
- Setiap signal yang keluar tercatat otomatis di journal dengan snapshot indikator yang dipakai.
- Confidence score < 50% selalu menghasilkan status WAIT, tidak pernah BUY/SELL.
- Auth lintas domain (Next.js ↔ Laravel) bekerja tanpa menyimpan token sensitif di localStorage.

## 23. Roadmap Pengembangan

**Fase 1 (MVP):** Setup Laravel API + Sanctum + Agent dasar (1 tool: indikator) + Next.js chat UI dasar tanpa realtime.
**Fase 2:** Tambah seluruh tools (OHLC, S/R, news), structured output lengkap, Reverb price ticker, journal dasar.
**Fase 3:** Dashboard performance lengkap, PWA, push notification, position size calculator, optimasi caching/rate limit.

## 24. Referensi & Dokumentasi

- Laravel AI SDK (`laravel/ai`) — dokumentasi resmi Laravel 13.x, modul Agent/Tools/Structured Output/Streaming.
- Laravel Sanctum — konfigurasi SPA authentication & stateful domains.
- Laravel Reverb — broadcasting realtime native Laravel.
- Vercel AI SDK (`ai`, `@ai-sdk/react`) — `useChat`, `DefaultChatTransport`, kompatibilitas Vercel AI Data Protocol.

---

*Dokumen ini melanjutkan PRD GoldAI Scalper v2.1 (Next.js + Supabase) dengan migrasi backend ke Laravel sesuai keputusan arsitektur terbaru.*