<?php

/**
 * @file
 * A form to collect an email address for RSVP details.
 */

namespace Drupal\rsvplist\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class RSVPForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rsvplist_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Attempt to get the fully loaded node object of the viewed page.
    $node = \Drupal::routeMatch()->getParameter('node');

    // Some pages may not be nodes though and $node will be NULL on those pages.
    // If a node was loaded, get the node id.
    if ( !(is_null($node)) ) {
      $nid = $node->id();
    }
    else {
      // If a node could not be loaded, default to 0;
      $nid = 0;
    }

    // Establish the $form render array. It has an email text field,
    // a submit button, and a hidden field containing the node nid.
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email address'),
      '#size' => 25,
      '#description' => t('We will send updates to the email address you provide.'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('RSVP'),
    ];

    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('email');
    if ( !(\Drupal::service('email.validator')->isValid($value)) ) {
      $form_state->setErrorByName('email',
        $this->t('It appears that %mail is not a valid email. Please try again',
        ['%mail' => $value]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //$submitted_email = $form_state->getValue('email');
    //$this->messenger()->addMessage(t('The form is working! You entered @entry',
    //  ['@entry' => $submitted_email]));
    try {
      // initiate variables to save

      //get current user id
      $uid= \Drupal::currentUser()->id();

      // Demonstration for how to load a full user object of the current user.
      // this $full_user variable is not needed forthis code,
      // but is shown for demonstration purposes.
      $full_user = \Drupal\user\Entity\User::load($uid);

      // obtain values as entered into the Form.
      $nid = $form_state->getValue('nid');
      $email = $form_state->getValue('email');

      $current_time = \Drupal::time()->getCurrentTime();
      // end phase 1.

      // Begin phase 2: save the values to the database.

      // start to build a query builder object $query.
      $query = \Drupal::database()->insert('rsvplist');

      //Specify the fields that the query will insert into
      $query->fields(
        [
          'nid',
          'mail',
          'uid',
          'created',
        ]
      );

      // Set the values of the fields we selected.
      // Note that they must be in the same order as we defined them
      // in the $query->fields() above.
      $query->values(
        [
          $nid,
          $email,
          $uid,
          $current_time,
        ]
      );

      // execute the query
      // drupal handles the exact syntax of the query automatically
      $query->execute();
      // end phase 2.

      //provide a form submitter a nice message
      \Drupal::messenger()->addMessage(
        t('Thank you for your RSVP, you are on the list for the event!')
      );
      // end phase 3.

    }
    catch (\Exception $e) {
      // if an error occurs, provide a message to the form submitter.
      \Drupal::messenger()->addMessage(
        t('Unable to save RSVP settings at this time due to database error.
        Please try again later.'
      )
    );
    }
  }
}
