# ADR Bundle for Symfony

This bundle makes it easier to follow ADR pattern while writing a [Symfony](https://symfony.com) application.


## Usage


### Turn controller into action

Decouple responder logic from action logic by moving it out of the controller. Just return the payload!

If a controller returns anything but a [`Response`](https://symfony.com/doc/current/components/http_foundation.html#response) object,
Symfony dispatches a `kernel.view` event.

Now instead of registering a bunch of event listeners to be iterated through,
implement [`ResponseHandlerInterface`](https://github.com/ph-fritsche/symfony-adr/blob/master/src/Responder/ResponseHandlerInterface.php).

```php
namespace App\Responder;

use Pitch\AdrBundle\Responder\ResponseHandlerInterface;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;
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

Your response handler can report its priority in `getSupportedTypes`.
```php
class MyPayloadHandler implements ResponseHandlerInterface
{
    public function getSupportedPayloadTypes(): array
    {
        return [
            MyPayload::class => 123,
            MyOtherPayload:class => 456,
        ];
    }
    //...
}
```

Or you can overwrite the handled types and priorities for response handlers in your `services.yml`.
```yml
services:
  App\Responder\MyPayloadHandler:
    tags:
      - name: pitch_adr.responder
        for: [App\Entity\MyPayload]
        priority: 1000
      - name: pitch_adr.responder
        for: [App\Entity\MyOtherPayload]
        priority: 0
```

You can easily debug your responder config per console command.
```
$ php bin/console debug:responder MyPayload
```

### Treat some exceptions as response payload

A robust domain will have strict constraints and throw exceptions whenever an unexpected or invalid condition occurs
and for every exception falling through your controller/action Symfony dispatches a `kernel.exception` event.

You can reserve this event for truly unexpected behavior without repeating similar try-catch-blocks across your controllers.

Define which exceptions should be catched for all controllers and be treated as response payload:
```yaml
pitch_adr:
    graceful:
        - { value: RuntimeException, not: [BadRuntime, OtherBadRuntime] }
        - Foo
        - { not: GloballyBadException }
        - { value: Bar, not: BadBar }
```

If [Doctrine Annotations](https://github.com/doctrine/annotations/) is installed,
you can define extra rules for your controller methods per annotation:
```php
namespace App\Controller;

use Pitch\AdrBundle\Configuration\Graceful;

class MyController
{
    /**
     * @Graceful(not=LocallyBadException::class)
     * @Graceful(LocallyGoodException::class, not={ButNotThisOne::class, OrThatOne::class})
     */
    public function __invoke(
        Request $request
    ) {
        /// ...
    }
}
```

With PHP8 you can define extra rules per [Attribute](https://www.php.net/manual/en/language.attributes.overview.php):
```php
namespace App\Controller;

use Pitch\AdrBundle\Configuration\Graceful;

class MyController
{
    #[Graceful(not: LocallyBadException::class)]
    #[Graceful(LocallyGoodException::class, not: [ButNotThisOne::class, OrThatOne::class])]
    public function __invoke(
        Request $request
    ) {
        /// ...
    }
}
```

Rules are applied in the order of appearance, method rules after global rules.

Now you can just create a `App\Responder\MyGoodRuntimeExceptionHandler` as described above.

### Default response handlers

The bundle automatically adds some response handlers for basic types with negative priority so that they will be called if none of your response handlers stops propagation earlier.
If you don't want the default handlers to be added, you can set `pitch_adr.defaultResponseHandlers: false` on your container parameters.

### Prioritised response handlers

If consecutive response handlers (in the order of config priority) implement [`PrioritisedResponseHandlerInterface`](https://github.com/ph-fritsche/symfony-adr/blob/master/src/Responder/PrioritisedResponseHandlerInterface.php), that block of handlers will be reordered on runtime according the priority they report for the specific request per `getResponseHandlerPriority`.

```
Given the following response handlers are configured to handle a `SomePayloadType`:

900: HandlerA
600: PrioritisedHandler1 with getResponseHandlerPriority(): 1
500: PrioritisedHandler2 with getResponseHandlerPriority(): 2
400: PrioritisedHandler3 with getResponseHandlerPriority(): 0
100: HandlerB

these will be executed in the following order:

HandlerA
PrioritisedHandler2
PrioritisedHandler1
PrioritisedHandler3
HandlerB
```

### Negotiating content type in response handlers

See [`JsonResponder`](https://github.com/ph-fritsche/symfony-adr/blob/master/src/Responder/Handler/JsonResponder.php) on how to implement your own prioritised response handlers that handle a payload according to the [Accept](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept) header on the request.

You can set a default content type for requests that don't include an Accept header.
This can be done per container parameter or as controller annotation.

```yml
parameters:
    pitch_adr.defaultContentType: 'application/json'
```

```php
use Pitch\AdrBundle\Configuration\DefaultContentType;

class MyController
{
    #[DefaultContentType('application/json')]
    public function __invoke()
    {
        // ...
    }
}
```
