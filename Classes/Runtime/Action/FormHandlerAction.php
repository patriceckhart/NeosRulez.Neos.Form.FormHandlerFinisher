<?php
declare(strict_types=1);

namespace NeosRulez\Neos\Form\FormHandlerFinisher\Runtime\Action;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\Form\Runtime\Action\AbstractAction;
use Neos\Flow\Mvc\ActionResponse;

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
        $this->sendRequest($formParams, $endpointURl);
        return null;
    }

    /**
     * @param array $formParams
     * @param string $endpoint
     * @return mixed
     */
    private function sendRequest(array $formParams, string $endpoint): mixed
    {
        $client = new Client();
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $request = new Request('POST', $endpoint, $headers);
        $res = $client->sendAsync($request, $formParams)->wait();
        return json_decode($res->getBody());
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
                $values[$itemKey] = $item;
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
