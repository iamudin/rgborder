<?php

namespace App\Livewire;

use App\Models\GameSession;
use App\Models\Setting;
use App\Models\Team;
use App\Services\BookingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class BookingForm extends Component
{
    public GameSession $session;

    public string $bookingType = 'individual'; // individual, group, full_team
    public ?int $targetTeamId = null; // null for auto-balance

    public string $primaryName = '';
    public string $primaryEmail = '';
    public string $primaryPhone = '';

    // Array of additional players for group or full_team: [['name' => '', 'phone' => '']]
    public array $additionalPlayers = [];

    public string $paymentMethod = 'qris'; // qris, bank_transfer, cash_on_site
    public bool $rulesAgreed = false;

    public string $errorMessage = '';

    public function mount(GameSession $session): void
    {
        $this->session = $session->load(['teams.bookingPlayers.booking']);

        if (Auth::check()) {
            $user = Auth::user();
            $this->primaryName = $user->name;
            $this->primaryEmail = $user->email;
            $this->primaryPhone = $user->phone ?? '';
        }

        $this->updateAdditionalPlayersCount();
    }

    public function updatedBookingType(): void
    {
        $this->updateAdditionalPlayersCount();
    }

    protected function updateAdditionalPlayersCount(): void
    {
        if ($this->bookingType === 'individual') {
            $this->additionalPlayers = [];
        } elseif ($this->bookingType === 'full_team') {
            // Needs 3 additional players (total 4)
            $this->additionalPlayers = [
                ['name' => '', 'phone' => ''],
                ['name' => '', 'phone' => ''],
                ['name' => '', 'phone' => ''],
            ];
        } elseif ($this->bookingType === 'group') {
            // Default 1 additional player (total 2)
            if (empty($this->additionalPlayers)) {
                $this->additionalPlayers = [
                    ['name' => '', 'phone' => ''],
                ];
            }
        }
    }

    public function addGroupPlayer(): void
    {
        if (count($this->additionalPlayers) < 3) {
            $this->additionalPlayers[] = ['name' => '', 'phone' => ''];
        }
    }

    public function removeGroupPlayer(int $index): void
    {
        if (count($this->additionalPlayers) > 1) {
            unset($this->additionalPlayers[$index]);
            $this->additionalPlayers = array_values($this->additionalPlayers);
        }
    }

    public function getTotalPlayersProperty(): int
    {
        return 1 + count($this->additionalPlayers);
    }

    public function getTotalAmountProperty(): int
    {
        return (int) $this->session->price_per_player * $this->totalPlayers;
    }

    public function submitBooking(BookingService $bookingService)
    {
        $this->errorMessage = '';

        if (! Auth::check()) {
            return redirect()->route('login', ['redirect_to' => route('booking.show', $this->session)])
                ->with('warning', 'Silakan masuk dengan akun Google Anda terlebih dahulu untuk menyelesaikan booking.');
        }

        $rules = [
            'primaryName' => ['required', 'string', 'min:2', 'max:50'],
            'primaryPhone' => ['required', 'string', 'regex:/^(\+62|62|0)8[1-9][0-9]{6,10}$/'],
            'bookingType' => ['required', 'in:individual,group,full_team'],
            'paymentMethod' => ['required', 'in:qris,bank_transfer,cash_on_site'],
            'rulesAgreed' => ['accepted'],
        ];

        if ($this->bookingType !== 'individual') {
            foreach ($this->additionalPlayers as $i => $player) {
                $rules["additionalPlayers.{$i}.name"] = ['required', 'string', 'min:2', 'max:50'];
                $rules["additionalPlayers.{$i}.phone"] = ['required', 'string', 'regex:/^(\+62|62|0)8[1-9][0-9]{6,10}$/'];
            }
        }

        $this->validate($rules, [
            'primaryPhone.regex' => 'Format nomor WhatsApp tidak valid (contoh: 081234567890).',
            'additionalPlayers.*.name.required' => 'Nama setiap anggota wajib diisi.',
            'additionalPlayers.*.phone.required' => 'Nomor WhatsApp anggota wajib diisi.',
            'additionalPlayers.*.phone.regex' => 'Format nomor WhatsApp anggota tidak valid.',
            'rulesAgreed.accepted' => 'Anda wajib menyetujui peraturan arena sebelum melanjutkan booking.',
        ]);

        // Build players array
        $playersData = [
            ['name' => $this->primaryName, 'phone' => $this->primaryPhone],
        ];

        foreach ($this->additionalPlayers as $p) {
            $playersData[] = [
                'name' => $p['name'],
                'phone' => $p['phone'],
            ];
        }

        // Update phone on user profile if not set
        $user = Auth::user();
        if (empty($user->phone)) {
            $user->update(['phone' => $this->primaryPhone]);
        }

        try {
            $booking = $bookingService->createCustomerBooking(
                $user,
                $this->session->id,
                $this->bookingType,
                $playersData,
                $this->targetTeamId,
                $this->paymentMethod
            );

            session()->flash('success', "Booking {$booking->booking_code} berhasil dibuat! Silakan lakukan pembayaran dalam batas waktu hold.");

            return redirect()->route('my-bookings.show', $booking->booking_code);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?? 'Terjadi kesalahan validasi.';
        } catch (\Throwable $e) {
            $this->errorMessage = 'Gagal membuat booking: '.$e->getMessage();
        }
    }

    public function render()
    {
        $holdDuration = Setting::get('hold_duration_minutes', 10);
        $bankName = Setting::get('bank_name', 'BCA');
        $bankNumber = Setting::get('bank_account_number', '8830192831');
        $bankHolder = Setting::get('bank_account_holder', 'RGB ARENA INDONESIA');

        return view('livewire.booking-form', compact('holdDuration', 'bankName', 'bankNumber', 'bankHolder'));
    }
}
