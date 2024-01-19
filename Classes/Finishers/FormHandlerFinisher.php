<?php
namespace NeosRulez\Neos\Form\FormHandlerFinisher\Finishers;

/*
 * This file is part of the NeosRulez.Neos.Form.FormHandlerFinisher package.
 */

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Form\Core\Model\AbstractFinisher;
use Neos\Form\Exception\FinisherException;
use Psr\Http\Message\UriFactoryInterface;

/**
 * This finisher send values to pardot form handler
 */
class FormHandlerFinisher extends AbstractFinisher
{

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var UriFactoryInterface
     */
    protected $uriFactory;

    /**
     * Executes this finisher
     * @return void
     * @throws FinisherException
     * @see AbstractFinisher::execute()
     *
     */
    protected function executeInternal()
    {
        $formRuntime = $this->finisherContext->getFormRuntime();
        $formValues = $formRuntime->getFormState()->getFormValues();

        $endpoint = $this->parseOption('endpoint');
        $node = $this->parseOption('node');

        $node = $this->parseOption('node');
        $formParams = $this->getFormParams($this->getValues($formValues, $node));
//        \Neos\Flow\var_dump($formParams);
        $response = $this->sendRequest($formParams, $endpoint);
//        \Neos\Flow\var_dump($response);

        $targetUri = '/';

        $escapedUri = htmlentities($targetUri, ENT_QUOTES, 'utf-8');
        $response = $formRuntime->getResponse();
        $response->setContent('<html><head><meta http-equiv="refresh" content="0;url=' . $escapedUri . '"/></head></html>');
        $response->setStatusCode('200');
        $response->setRedirectUri($this->uriFactory->createUri((string) $targetUri));
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
     * @param array $formValues
     * @return array
     */
    private function getFormParams(array $formValues): array
    {
        $result['form_params'] = [];
        foreach ($formValues as $formValue) {
            $result['form_params'][$formValue['formHandlerId']] = $formValue['value'];
        }
        return $result;
    }

    /**
     * @param array $formValues
     * @param Node $node
     * @return array
     */
    private function getValues(array $formValues, Node $node): array
    {
        $formData = [];
        $unrealIdentifiers = [];
        $inputs = (new FlowQuery(array($node)))->find('[instanceof Neos.Form.Builder:FormElement]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->filter('[label != false]')->get();
        foreach ($inputs as $input) {
            if($input->hasProperty('identifier')) {
                $unrealIdentifiers[$input->getProperty('identifier')] = $input->getIdentifier();
            }
        }
        $context = $this->contextFactory->create();
        foreach ($formValues as $i => $value) {
            if(array_key_exists($i, $unrealIdentifiers)) {
                $node = $context->getNodeByIdentifier($unrealIdentifiers[$i]);
            } else {
                $node = $context->getNodeByIdentifier($i);
            }
            $formData[] = ['key' => $node->getProperty('label'), 'formHandlerId' => $node->getProperty('formHandlerId'), 'value' => $value];
        }
        return $formData;
    }

}
