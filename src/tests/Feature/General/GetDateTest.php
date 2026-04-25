<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginForm;
use Tests\TestCase;
use App\Models\User;
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

        $now = now();

        // 1. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertStatus(200);

        // 2. 画面に表示されている日時情報を確認する
        // 画面上に表示されている日時が現在の日時と一致する
        $response->assertSeeText($now->format('H:i'));
    }
}