<?php

namespace App\Http\Middleware;

use Closure;

class TokenVerify
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            if (!isset($request->_token)) {
                return response('Not Found', 404);
            }

            $time = decrypt($request->_token);

            if (empty($time) || !\is_int($time)) {
                throw new \Exception('系統異常，請重新登入！');
            }

            if (time() - $time > (int) config('session.lifetime') * 60) {
                throw new \Exception('連線逾時，請重新登入！');
            }
        } catch (\Exception $e) {
            $result = [
                'error' => true,
                'msg' => $e->getMessage(),
            ];

            return response()->json($result);
        }

        return $next($request);
    }
}
