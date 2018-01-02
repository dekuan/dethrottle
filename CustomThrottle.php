<?php
/**
 * Created by PhpStorm.
 * User: huizhi
 * Date: 2018/1/2
 * Time: 10:09
 */

namespace App\Http\Middleware;


use Closure;
use Illuminate\Redis\Limiters\DurationLimiter;
use Illuminate\Contracts\Redis\Factory as Redis;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Request;

class CustomThrottle extends ThrottleRequests
{
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * The timestamp of the end of the current duration.
     *
     * @var int
     */
    public $decaysAt;

    /**
     * The number of remaining slots.
     *
     * @var int
     */
    public $remaining;

    /**
     * Create a new request throttler.
     *
     * @param  \Illuminate\Contracts\Redis\Factory $redis
     * @return void
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  int|string $maxAttempts
     * @param  float|int $decayMinutes
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next, $sType = 'ip', $maxAttempts = 60, $decayMinutes = 1)
    {

        $key = $this->getKeyByType($sType) . $this->resolveRequestSignature($request);

        $maxAttempts = $this->resolveMaxAttempts($request, $maxAttempts);

        if ($this->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            return json_encode(['e_no'=>429,'e_msg'=>'too many request,please wait a moment!']);
            //throw $this->buildException($key, $maxAttempts);
        } else {

            $response = $next($request);

            return $this->addHeaders(
                $response, $maxAttempts,
                $this->calculateRemainingAttempts($key, $maxAttempts)
            );
        }
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param  string $key
     * @param  int $maxAttempts
     * @param  int $decayMinutes
     * @return mixed
     */
    protected function tooManyAttempts($key, $maxAttempts, $decayMinutes)
    {
        $limiter = new DurationLimiter(
            $this->redis, $key, $maxAttempts, $decayMinutes * 60
        );
        //dd($limiter);

        return tap(!$limiter->acquire(), function () use ($limiter) {
            list($this->decaysAt, $this->remaining) = [
                $limiter->decaysAt, $limiter->remaining,
            ];
        });
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param  string $key
     * @param  int $maxAttempts
     * @param  int|null $retryAfter
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null)
    {
        if (is_null($retryAfter)) {
            return $this->remaining;
        }

        return 0;
    }

    /**
     * Get the number of seconds until the lock is released.
     *
     * @param  string $key
     * @return int
     */
    protected function getTimeUntilNextRetry($key)
    {
        return $this->decaysAt - $this->currentTime();
    }


    protected function getKeyByType($sType)
    {
        switch (strtolower($sType)) {
            case 'ip':
                $sKey = $this->getIp();
                break;
            case 'umid':
                $sKey = $this->getUMid();
                break;
            case 'sms';
                $sKey = $this->getMobile();
                break;
            case 'inter';
                $sKey = $this->getInterfaceName();
                break;
            default:
                $sKey = "";
        }

        return $sKey;
    }

    //get real name
    protected function getIp()
    {
        $sIp = '';
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $sIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $sIp = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $sIp = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $sIp = getenv('HTTP_X_FORWARDED_FOR');
            } else if (getenv('HTTP_CLIENT_IP')) {
                $sIp = getenv('HTTP_CLIENT_IP');
            } else {
                $sIp = getenv('REMOTE_ADDR');
            }
        }
        return $sIp;
    }

    //get current interface name
    protected function getInterfaceName()
    {
        return Request::getRequestUri();
    }


    //get  user's UMid
    protected function getUMid()
    {

    }

    // get  user    Mobile
    protected function getMobile()
    {

    }
}