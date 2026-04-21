<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginForm;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GetDateTest extends TestCase
{
    /**
     * 日時取得機能
     */
    use RefreshDatabase;

    /**
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    #[Test]
    public function currentDatetimeIsDisplayedInUiFormat() {

        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);

        $now = now()->format('H:i');

        // 1. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertStatus(200);

        // 2. 画面に表示されている日時情報を確認する
        $response->assertSee($now);

        // タイミングによっては時刻がずれる可能性があるので現在時刻を固定して再度確認
        Carbon::setTestNow(Carbon::create(2026, 4, 20, 9, 30));
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertStatus(200);
        $response->assertSee('09:30');
    }
}