<?php
declare(strict_types=1);

namespace NeosRulez\Neos\Form\FormHandlerFinisher\Runtime\Action;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Neos\Fusion\Form\Runtime\Action\AbstractAction;
use Neos\Flow\Mvc\ActionResponse;
use Psr\Http\Message\StreamInterface;

class FormHandlerAction extends AbstractAction
{
    /**
     * @return ActionResponse|null
     */
    public function perform(): ?ActionResponse
    {
        $formData = $this->options['formData'];
        $formParams = $this->getFormParams($formData);
        $endpointURl = $this->getEndpointUrl($formData);
//        \Neos\Flow\var_dump($formParams);
//        \Neos\Flow\var_dump($endpointURl);
        $this->sendRequest($formParams, $endpointURl);
//        \Neos\Flow\var_dump($foo);
        return null;
    }

    /**
     * @param array $formParams
     * @param string $endpoint
     * @return StreamInterface
     */
    private function sendRequest(array $formParams, string $endpoint): StreamInterface
    {
        $client = new Client();
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $request = new Request('POST', $endpoint, $headers);
//        $res = $client->sendAsync($request, $formParams)->wait();
        $res = $client->send($request, $formParams);
        return $res->getBody();
    }

    /**
     * @param array $formData
     * @return array
     */
    private function getFormParams(array $formData): array
    {
        $values = [];
        foreach ($formData as $itemKey => $item) {
            if($itemKey !== 'endpointUrl') {
                $values[$itemKey] = (string) $item;
            }
        }
        return [
            'form_params' => $values
        ];
    }

    /**
     * @param array $formData
     * @return string
     */
    private function getEndpointUrl(array $formData): string
    {
        return $formData['endpointUrl'];
    }

}
