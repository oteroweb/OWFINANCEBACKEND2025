<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\AccountRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    private $accountRepo;

    public function __construct(AccountRepo $accountRepo)
    {
        $this->accountRepo = $accountRepo;
    }

    /**
     * @group Account
     * Get all accounts
     */
    public function all(Request $request)
    {
        try {
            $params = $request->only(['page','per_page','sort_by','descending','search','active','currency_id','currency','account_type_id','account_type','user_id','user','is_owner']);
            $accounts = $this->accountRepo->all($params);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Accounts Obtained Correctly'),
                'data'    => $accounts,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * @group Account
     * Get all active accounts
     */
    public function allActive(Request $request)
    {
        try {
            $params = $request->only(['page','per_page','sort_by','descending','search','active','currency_id','currency','account_type_id','account_type','user_id','user','is_owner']);
            $accounts = $this->accountRepo->allActive($params);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $accounts,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * @group Account
     * Find an account by ID
     * @urlParam id integer required The ID of the account. Example: 1
     */
    public function find($id)
    {
        try {
            $account = $this->accountRepo->find($id);
            if (isset($account->id)) {
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Account Obtained Correctly'),
                    'data'    => $account,
                ];
                return response()->json($response, 200);
            }
            $response = [
                'status'  => 'FAILED',
                'code'    => 404,
                'message' => __('Not Data with this Account') . '.',
            ];
            return response()->json($response, 404);
        } catch (\Exception $ex) {
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * @group Account
     * Save a new account
     * @bodyParam name string required The name of the account. Example: Account 1
     * @bodyParam currency_id integer required The ID of the currency. Example: 1
     * @bodyParam initial number required The initial amount. Example: 1000.00
     * @bodyParam account_type_id integer required The ID of the account type. Example: 1
     */
    public function save(Request $request)
    {
    $validator = Validator::make($request->all(), [
            'name' => 'required|max:35',
            'currency_id' => 'required|exists:currencies,id',
            'initial' => 'required|numeric',
            'account_type_id' => 'required|exists:account_types,id',
            'active' => 'sometimes|boolean',
        ], $this->custom_message());

        if ($validator->fails()) {
            $response = [
                'status'  => 'FAILED',
                'code'    => 400,
                'message' => __('Incorrect Params'),
                'data'    => $validator->errors()->getMessages(),
            ];
            return response()->json($response, 400);
        }

        try {
            $data = $request->only(['name', 'currency_id', 'initial', 'account_type_id']);
            if ($request->exists('active')) {
                $data['active'] = $request->boolean('active');
            }
            $account = $this->accountRepo->store($data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Account saved correctly'),
                'data'    => $account,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * @group Account
     * Update an existing account
     * @urlParam id integer required The ID of the account. Example: 1
     * @bodyParam name string optional The name of the account. Example: Account 1x
     * @bodyParam currency_id integer optional The ID of the currency. Example: 1
     * @bodyParam initial number optional The initial amount. Example: 1200.50
     * @bodyParam account_type_id integer optional The ID of the account type. Example: 1
     */
    public function update(Request $request, $id)
    {
        $account = $this->accountRepo->find($id);
        if (isset($account->id)) {
            $data = $request->only(['name', 'currency_id', 'initial', 'account_type_id']);
            if ($request->exists('active')) {
                $data['active'] = $request->boolean('active');
            }
            $account = $this->accountRepo->update($account, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Account updated'),
                'data'    => $account,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 404,
            'message' => __('Account does not exist') . '.',
        ];
        return response()->json($response, 404);
    }

    /**
     * @group Account
     * Delete an account
     * @urlParam id integer required The ID of the account. Example: 1
     */
    public function delete($id)
    {
        try {
            $account = $this->accountRepo->find($id);
            if ($account) {
                $account = $this->accountRepo->delete($account);
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Account Deleted Successfully'),
                    'data'    => $account,
                ];
                return response()->json($response, 200);
            } else {
                $response = [
                    'status'  => 'FAILED',
                    'code'    => 404,
                    'message' => __('Account not Found'),
                ];
                return response()->json($response, 404);
            }
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * @group Account
     * Change account status
     * @urlParam id integer required The ID of the account. Example: 1
     */
    public function change_status($id)
    {
        $account = $this->accountRepo->find($id);
        if (isset($account->active)) {
            $data = ['active' => !$account->active];
            $this->accountRepo->update($account, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status Account updated'),
                'data'    => $account,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 404,
            'message' => __('Account does not exist') . '.',
        ];
        return response()->json($response, 404);
    }

    /**
     * @group Account
     * Get accounts with trashed
     */
    public function withTrashed()
    {
        try {
            $accounts = $this->accountRepo->withTrashed();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $accounts,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    public function custom_message()
    {
        return [
            'name.required' => __('The name is required'),
            'currency_id.required' => __('The currency is required'),
            'initial.required' => __('The initial amount is required'),
            'account_type_id.required' => __('The account type is required'),
        ];
    }
}
