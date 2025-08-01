<?php
    
    namespace App\Models\Repositories;
    
    use Illuminate\Support\Facades\Log;
    use App\Models\Entities\Currency;
    
    class CurrencyRepo {
        public function all() {
            $currencies = Currency::whereIn('active', [1,0])->with([])->get();
            // Asegura que cada currency tenga el campo tax (puedes ajustar la lógica si es necesario)
            foreach ($currencies as $currency) {
                if (!isset($currency->tax)) {
                    $currency->tax = null;
                }
            }
            return $currencies;
        }
        public function allActive() {
            $currencies = Currency::whereIn('active', [1])->with([])->get();
            foreach ($currencies as $currency) {
                if (!isset($currency->tax)) {
                    $currency->tax = null;
                }
            }
            return $currencies;
        }
        public function find($id) {
            $currency = Currency::with([])->find($id);
            return $currency;
        }        
        public function store($data) {            
            $currency = new Currency();
            $currency->fill($data);
            $currency->save();
            return $currency;
        }        
        public function update($currency, $data) {
            $currency->fill($data);
            $currency->save();
            return $currency;
        }
        public function delete($currency, $data) {
            $currency->fill($data);
            $currency->save();
            return $currency;
        }
        public function withTrashed() {
            $currencies = Currency::withTrashed()->get();
            foreach ($currencies as $currency) {
                if (!isset($currency->tax)) {
                    $currency->tax = null;
                }
            }
            return $currencies;
        }
    }