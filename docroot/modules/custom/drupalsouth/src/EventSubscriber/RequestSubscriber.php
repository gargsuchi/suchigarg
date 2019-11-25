<?php

namespace Drupal\drupalsouth\EventSubscriber;
namespace Drupal\drupalsouth\EventSubscriber;

use Drupal\alexa\AlexaEvent;
use Drupal\views\Views;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * An event subscriber for Alexa request events.
 */
class RequestSubscriber implements EventSubscriberInterface {

  /**
   * Gets the event.
   */
  public static function getSubscribedEvents() {
    $events['alexaevent.request'][] = array('onRequest', 0);
    return $events;
  }

  /**
   * Called upon a request event.
   *
   * @param \Drupal\alexa\AlexaEvent $event
   *   The event object.
   */
  public function onRequest(AlexaEvent $event) {
    $request = $event->getRequest();
    $response = $event->getResponse();

    $type = get_class($request);

    if ($type == "Alexa\Request\SessionEndedRequest") {
      $response->respond('Okay. Bye.')
        ->endSession();
    }
    elseif ($type == "Alexa\Request\LaunchRequest") {
      // The skill was just launched, so welcome the user and provide help.
      $response->respondSSML('<speak><say-as interpret-as="interjection">Welcome to cooking with Drupal!</say-as>
      Do you want to search for a recipe using an ingredient?<break strength="medium"/>
      Or do you want to find the ingredients of a recipe?<break strength="medium"/>
      I can also tell you the steps of a recipe.</speak>');
    }
    elseif ($type == "Alexa\Request\IntentRequest") {
      switch ($request->intentName) {
        case 'CaptureIngredientIntent':
          // Get the {Ingredient} slot's value.
          $ingredient = $request->getSlot('Ingredient');
          $view = Views::getView('recipe_list');
          $view->setDisplay('block_1');
          $view->setArguments([$ingredient]);
          $view->execute();
          if (empty($view->build_info['fail']) and empty($view->build_info['denied'])) {
            $result = $view->result;
            $response_text = '<speak><say-as interpret-as="interjection">' . "These are the recipes for $ingredient" . '</say-as><break strength="medium"/>';
            if (count($result)) {
              foreach ($result AS $id => $row) {
                foreach ($view->field as $fid => $field) {
                  if ($fid == 'title') {
                    $response_text .= $field->getValue($row) . '<break strength="strong"/>';
                  }
                }
              }
              $response_text .= '</speak>';
            }
            else {
              $response_text = "<speak>Sorry. I did not find any recipes for $ingredient.</speak>";
            }
          }
          else {
            $response_text = "<speak>Sorry. I did not find any recipes for $ingredient. It seems something went wrong.</speak>";
          }
          $response->respondSSML($response_text);
          break;

        case 'FindRecipeIntent':
          // Get the {recipe} slot's value.
          $recipe = $request->getSlot('recipe');
          $nodes = \Drupal::entityTypeManager()
		  ->getStorage('node')
		  ->loadByProperties(['title' => $recipe]);
            $response_text = '<speak><say-as interpret-as="interjection">' . "The recipe for $recipe is " . '</say-as><break strength="medium"/>';
            if (count($nodes)) {
              foreach ($nodes AS $node){
		    $body = $node->body->value;
		    //$body = $node->get('body')->getString();;
                    $response_text .= $body . '<break strength="strong"/>';
              }
              $response_text .= '</speak>';
            }
            else {
              $response_text = "<speak>Sorry. I did not find any recipes named $recipe.</speak>";
            }
          $response->respondSSML($response_text);
          break;

        case 'RecipeIngredientsIntent':
          // Get the {recipe} slot's value.
          $recipe = $request->getSlot('recipe');
          $nodes = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['title' => $recipe]);
          $response_text = '<speak><say-as interpret-as="interjection">' . "The ingredients for $recipe are " . '</say-as><break strength="medium"/>';
          if (count($nodes)) {
            foreach ($nodes AS $node){
              $body = $node->field_all_ingredients->value;
              //$body = $node->get('body')->getString();;
              $response_text .= $body . '<break strength="strong"/>';
            }
            $response_text .= '</speak>';
          }
          else {
            $response_text = "<speak>Sorry. I did not find any recipes named $recipe.</speak>";
          }
          $response->respondSSML($response_text);
          break;

        case 'AMAZON.HelpIntent':
          $response_text = '<speak><say-as interpret-as="interjection">' . "You can say any of the following " . '</say-as><break strength="medium"/>';
          $response_text .= 'You can say "What can I make with paneer", and I will list recipes for paneer. <break strength="strong"/>';
          $response_text .= 'You can say "How do I make palak paneer", and I will list steps for making palak paneer. <break strength="strong"/>';
          $response_text .= 'You can say "What are the ingredients for palak paneer", and I will list ingredients for palak paneer. <break strength="strong"/>';
          $response_text .= '</speak>';
          $response->respondSSML($response_text);
          break;

        case 'AMAZON.ExitIntent':
          $response->respond('Okay. Bye.')
            ->endSession();
          break;

        default:
          $response->respond('Welcome to Drupal Cooking! Which ingredient would you like to search recipes for?');
          break;
      }
    }
    elseif ($request instanceof SessionEndedRequest) {
      // @todo: Clean up any saved session state here.
    }
    else {
      \Drupal::logger('alexa_demo')
        ->warning('Request was not an expected request type: @type', [
          '@type' => get_class($request),
        ]);
    }
  }

}
