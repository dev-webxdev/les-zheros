<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title',
    'category',
    'anomaly_type',
    'anomaly_level',
    'dream_type',
    'dream_floor',
    'guildatons',
    'activity_points',
    'image_mode',
    'image_path',
    'monster_id',
])]
class Mission extends Model
{
    use SoftDeletes;

    public const CATEGORIES = [
        'donjon' => 'Donjon',
        'regulation' => 'Régulation',
        'expedition' => 'Expédition',
        'anomalie' => 'Anomalie',
        'songe' => 'Songe',
    ];

    public const ANOMALY_TYPES = [
        'dungeon_guardian' => 'Gardien de donjon',
        'anomaly_monster' => "Monstre d'anomalie",
    ];

    public const ANOMALY_LEVELS = [
        110,
        120,
        130,
        140,
        150,
        160,
        170,
        180,
        190,
        200,
    ];

    public const DREAM_TYPES = [
        'reve_1' => 'Rêve 1',
        'reve_2' => 'Rêve 2',
        'reve_3' => 'Rêve 3',
        'paradoxe_1' => 'Paradoxe 1',
        'paradoxe_2' => 'Paradoxe 2',
        'paradoxe_3' => 'Paradoxe 3',
        'paradoxe_4' => 'Paradoxe 4',
        'cauchemar_1' => 'Cauchemar 1',
        'cauchemar_2' => 'Cauchemar 2',
        'cauchemar_3' => 'Cauchemar 3',
    ];

    protected function casts(): array
    {
        return [
            'dream_floor' => 'integer',
            'anomaly_level' => 'integer',
            'guildatons' => 'integer',
            'activity_points' => 'integer',
        ];
    }

    public function guide(): HasOne
    {
        return $this->hasOne(Guide::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function dreamTypeLabel(): ?string
    {
        return $this->dream_type ? self::DREAM_TYPES[$this->dream_type] ?? $this->dream_type : null;
    }

    public function anomalyTypeLabel(): ?string
    {
        return $this->anomaly_type ? self::ANOMALY_TYPES[$this->anomaly_type] ?? $this->anomaly_type : null;
    }

    public static function anomalyTitle(?string $type, int|string|null $level): string
    {
        $level = (int) $level;

        return match ($type) {
            'dungeon_guardian' => "Vaincre un gardien de donjon sous anomalie de niveau {$level} ou +",
            'anomaly_monster' => "Vaincre 50 monstres dans un territoire {$level} ou +",
            default => '',
        };
    }

    public function cardClass(): string
    {
        return 'mission-card--'.$this->category;
    }

    public function typeClass(): string
    {
        return 'mission-card-type--'.$this->category;
    }

    public function badgePath(): string
    {
        return match ($this->category) {
            'regulation' => 'assets/img/card-mission/regulation.png',
            'expedition' => 'assets/img/card-mission/expedition.png',
            'anomalie' => 'assets/img/card-mission/anomalie.png',
            'songe' => 'assets/img/card-mission/songe.png',
            default => 'assets/img/card-mission/type.png',
        };
    }

    public function songeImagePath(): ?string
    {
        if ($this->category !== 'songe' || blank($this->dream_type)) {
            return null;
        }

        $filename = str_replace('_', '-', $this->dream_type).'.png';
        $path = 'assets/img/songes/'.$filename;

        return file_exists(public_path($path)) || file_exists(base_path($path)) ? $path : null;
    }

    public function songeImageUrl(): ?string
    {
        $path = $this->songeImagePath();

        return $path ? asset($path) : null;
    }

    public function imageUrl(): string
    {
        if ($this->category === 'anomalie') {
            return asset('assets/img/card-mission/zaap-anomalie.png');
        }

        if ($this->category === 'songe') {
            return $this->songeImageUrl() ?: asset($this->badgePath());
        }

        return $this->image_path ?: asset($this->badgePath());
    }

    public function description(): string
    {
        if ($this->category === 'songe') {
            $type = $this->dreamTypeLabel() ?: 'un songe';
            $floor = $this->dream_floor ? 'palier '.$this->dream_floor : 'palier à définir';

            return "Terminer {$type}, {$floor}, et valider <strong>{$this->title}</strong>.";
        }

        if ($this->category === 'anomalie') {
            return '';
        }

        if ($this->category === 'regulation') {
            return "Vaincre 50 <strong>{$this->title}</strong> sur leur territoire.";
        }

        if ($this->category === 'donjon') {
            return "Vaincre <strong>le {$this->title}</strong> dans son donjon.";
        }

        if ($this->category === 'expedition') {
            return "Vaincre <strong>le {$this->title}</strong> dans son expédition de l'audace.";
        }

        return "Vaincre <strong>{$this->title}</strong> et rapporter la preuve.";
    }
}
