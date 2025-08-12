<?php
    
    namespace App\Models\Repositories;
    
    use Illuminate\Support\Facades\Log;
    use App\Models\Entities\Provider;
    
    class ProviderRepo {
        public function all() {
            $provider = Provider::whereIn('active', [1,0])->with(['user'])
            ->get();            
            return $provider;
        }
        public function allActive() {
            $provider = Provider::whereIn('active', [1])->with(['user'])
            ->get();            
            return $provider;
        }
        public function find($id) {
            $provider = Provider::with(['user'])->find($id);
            return $provider;
        }        
        public function store($data) {            
            $provider = new Provider();
            $provider->fill($data);
            $provider->save();
            return $provider;
        }        
        public function update($provider, $data) {
            $provider->fill($data);
            $provider->save();
            return $provider;
        }
        public function delete($provider, $data) {
            $provider->fill($data);
            $provider->save();
            return $provider;
        }
        public function withTrashed() {
            $provider = Provider::withTrashed()->with(['user'])->get();
            return $provider;
        }
    }