<?php

namespace Tests\Unit;

use App\Support\AdminDate;
use Carbon\Carbon;
use Tests\TestCase;

class AdminDateTest extends TestCase
{
    public function test_admin_input_is_converted_from_kigali_time_to_utc(): void
    {
        $date = AdminDate::fromInput('2026-10-21T12:00');

        $this->assertSame('2026-10-21 10:00', $date->format('Y-m-d H:i'));
        $this->assertSame('UTC', $date->timezoneName);
    }

    public function test_utc_values_are_shown_in_kigali_time_and_round_trip(): void
    {
        $utc = Carbon::parse('2026-10-21 10:00', 'UTC');

        $this->assertSame('2026-10-21T12:00', AdminDate::toInput($utc));
        $this->assertSame('2026-10-21 10:00', AdminDate::fromInput(AdminDate::toInput($utc))->format('Y-m-d H:i'));
    }

    public function test_blank_values_stay_null(): void
    {
        $this->assertNull(AdminDate::fromInput(null));
        $this->assertNull(AdminDate::fromInput(''));
        $this->assertNull(AdminDate::toInput(null));
    }
}
