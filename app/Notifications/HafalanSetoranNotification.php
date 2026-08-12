<?php

namespace App\Notifications;

use App\Models\Hafalan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class HafalanSetoranNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Hafalan $hafalan) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $hafalan = $this->hafalan->loadMissing(['santri:id,name', 'kategori:id,nama']);

        return (new MailMessage)
            ->subject(__('Setoran hafalan baru: :santri', ['santri' => $hafalan->santri?->name]))
            ->greeting(__('Assalamu\'alaikum, :name', ['name' => $notifiable->name]))
            ->line(__(':santri telah menyetorkan hafalan pada :tanggal.', [
                'santri' => $hafalan->santri?->name ?? '-',
                'tanggal' => $hafalan->tanggal->format('d F Y'),
            ]))
            ->line(new HtmlString(
                '<strong>'.$hafalan->surat.'</strong>'.
                ($hafalan->juz ? ' (Juz '.$hafalan->juz.')' : '').
                ($hafalan->kategori ? '<br>Kategori: '.$hafalan->kategori->nama : '')
            ))
            ->line(__('Tipe: :type', ['type' => $hafalan->type->label()]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $hafalan = $this->hafalan->loadMissing(['santri:id,name,nis', 'kategori:id,nama', 'ustad:id,name']);

        return [
            'hafalan_id' => $hafalan->id,
            'team_id' => $hafalan->team_id,
            'santri_id' => $hafalan->santri_id,
            'santri_name' => $hafalan->santri?->name,
            'santri_nis' => $hafalan->santri?->nis,
            'type' => $hafalan->type->value,
            'type_label' => $hafalan->type->label(),
            'surat' => $hafalan->surat,
            'juz' => $hafalan->juz,
            'ayat_from' => $hafalan->ayat_from,
            'ayat_to' => $hafalan->ayat_to,
            'tanggal' => $hafalan->tanggal->format('Y-m-d'),
            'kategori' => $hafalan->kategori ? ['id' => $hafalan->kategori->id, 'nama' => $hafalan->kategori->nama] : null,
            'ustad_name' => $hafalan->ustad?->name,
        ];
    }
}
