<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\TransactionType;

class TransactionTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_type_crud()
    {
        // create
        $payload = ['name'=>'Income','slug'=>'income'];
        $create = $this->postJson('/api/v1/transaction_types', $payload);
        $create->assertStatus(201)->assertJson(['status'=>'OK']);
        $id = $create->json('data.id');

        // read
        $find = $this->getJson('/api/v1/transaction_types/'.$id);
        $find->assertStatus(200)->assertJson(['data'=>['slug'=>'income']]);

        // list
        $list = $this->getJson('/api/v1/transaction_types');
        $list->assertStatus(200);

        // update
        $update = $this->putJson('/api/v1/transaction_types/'.$id, ['name'=>'Income Updated']);
        $update->assertStatus(200);

        // change status
        $status = $this->patchJson('/api/v1/transaction_types/'.$id.'/status');
        $status->assertStatus(200);

        // delete
        $delete = $this->deleteJson('/api/v1/transaction_types/'.$id);
        $delete->assertStatus(200);
    }
}
