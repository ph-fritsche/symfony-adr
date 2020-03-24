# ADR Bundle for Symfony

This bundle makes it easier to follow ADR pattern while writing a [Symfony](https://symfony.com) application.


## Usage


### Turn controller into action

Decouple responder logic from action logic by moving it out of the controller. Just return the payload!

If a controller returns anything but a `Response` object, Symfony dispatches a `kernel.view` event.

Now instead of registering a bunch of event listeners to be iterated through, implement `ResponseHandlerInterface`.

```php
namespace App\Responder;

use nextdev\AdrBundle\Responder\ResponseHandlerInterface;
use nextdev\AdrBundle\Responder\ResponsePayloadEvent;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\MyPayload;

class MyPayloadHandler implements ResponseHandlerInterface
{
    public function getSupportedPayloadTypes(): array
    {
        return [
            MyPayload::class,
        ];
    }

    public function handleResponsePayload(
        ResponsePayloadEvent $payloadEvent
    ): void {
        $response = new Response();

        // prepare the response
        if ($payloadEvent->request->getAttribute('_foo') === 'bar') {
            // adjust the response according to the request
        }

        $payloadEvent->payload = $response;
    }
}
```

If your handler class is available as a service according to your `config/services.yaml`,
it will be discovered and used whenever a `MyPayload` object is returned by a controller.

With default config just put the class into `src/Responder/MyPayloadHandler.php` and you are done.


### Treat some exceptions as response payload

A robust domain will have strict constraints and throw exceptions whenever an unexpected or invalid condition occurs
and for every exception falling through your controller/action Symfony dispatches a `kernel.exception` event.

You can reserve this event for truly unexpected behavior without repeating similar try-catch-blocks across your controllers.

Define which exceptions should be catched for all controllers and be treated as response payload:
```yaml
nextdev_adr:
    graceful:
        - { value: RuntimeException, not: [BadRuntime, OtherBadRuntime] }
        - Foo
        - { not: GloballyBadException }
        - { value: Bar, not: BadBar }
```

If [SensioFrameworkExtraBundle](https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/index.html) is installed,
you can define extra rules for your controller methods per annotation:
```php
namespace App\Controller;

use nextdev\AdrBundle\Configuration\Graceful;

class MyController
{
    /**
     * @Graceful(not={LocallyBadException})
     * @Graceful(LocallyGoodException, not={ButNotThisOne, OrThatOne})
     */
    public function __invoke(
        Request $request
    ) {
        /// ...
    }
}
```

Rules are applied in the order of appearance, method rules after global rules.

Now you can just create a `App\Responder\MyGoodRuntimeExceptionHandler` as described above.

