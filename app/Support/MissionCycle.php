<?php

namespace App\Support;

use App\Models\GuildSetting;
use Carbon\CarbonImmutable;

class MissionCycle
{
    public function sync(): void
    {
        $end = $this->configuredEnd();
        $now = CarbonImmutable::now();

        if ($end->greaterThan($now)) {
            return;
        }

        while ($end->lessThanOrEqualTo($now)) {
            $end = $end->addWeek();
        }

        GuildSetting::setMany([
            GuildSetting::MISSION_CYCLE_END => $end->format('Y-m-d\TH:i'),
        ]);
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable, value: string, label: string}
     */
    public function current(): array
    {
        $end = $this->configuredEnd();
        $start = $end->subWeek();

        return $this->period($start, $end);
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable, value: string, label: string}
     */
    public function previous(): array
    {
        $current = $this->current();

        return $this->period(
            $current['start']->subWeek(),
            $current['start'],
        );
    }

    private function configuredEnd(): CarbonImmutable
    {
        return CarbonImmutable::parse(GuildSetting::values()[GuildSetting::MISSION_CYCLE_END]);
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable, value: string, label: string}
     */
    private function period(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return [
            'start' => $start,
            'end' => $end,
            'value' => $start->format('Y-m-d_H-i'),
            'label' => 'Cycle du '.$start->format('d/m/Y H:i').' au '.$end->format('d/m/Y H:i'),
        ];
    }
}
