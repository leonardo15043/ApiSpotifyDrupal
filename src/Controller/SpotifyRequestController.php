<?php

/**
 * @file
 * Contains \Drupal\spotify\Controller\SpotifyRequestController.
 */

namespace Drupal\spotify\Controller;

use Drupal\spotify\Api\SpotifyWebAPIAuthException;
use Drupal\spotify\Api\SpotifyWebAPIException;
use Drupal\spotify\Api\SpotifyWebAPI;
use Drupal\spotify\Api\Session;
use Drupal\spotify\Api\Request;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;

class SpotifyRequestController extends ControllerBase{

  private $accessToken;
  private $session;

  public function __construct() {

    $this->session = \Drupal::request()->getSession();
    $this->accessToken = $this->session->get('accessToken');

  }

  public function getReleases(){

      $api = new SpotifyWebAPI();

      try {

            $api->setAccessToken($this->accessToken);

            $releases = $api->getNewReleases([
                'country' => 'co',
            ]);

            $header = [
              'picture' => '',
              ['data' => t('Nombre')],
              ['data' => t('Artista')],
            ];

            $cont = 0;
            $content = [];
            foreach ($releases->albums->items as $list) {
              $content[$cont] =[
                'picture' => [
                  'data' => [
                    '#type' => 'html_tag',
                    '#tag' => 'img',
                    '#attributes' => ['src' => $list->images[0]->url],
                  ],
                ],
              ];
              $content[$cont]['name'] = $list->name;

              foreach ($list->artists as $artist) {
                $link = Link::fromTextAndUrl(t($artist->name), Url::fromRoute('artists.view'  , array('idartist' => $artist->id) ));
                $content[$cont]['artist'] = $link;
              }
              $cont++;
            }

            $results = [
              '#type' => 'table',
              '#header' => $header,
              '#rows' => $content,
               '#cache' => [
                 'max-age' => 0,
                ]
            ];

            return $results;

      } catch (SpotifyWebAPIException $e) {

        return new RedirectResponse(\Drupal::url('spotify.login'));

      }

  }

  public function getArtists(){

    if(!$this->session->get('accessToken')){
       return new RedirectResponse(\Drupal::url('spotify.login'));
    }

    $idArtist = \Drupal::request()->query->get('idartist');
    $api = new SpotifyWebAPI();
    $api->setAccessToken($this->accessToken);

    $artists = $api->getArtists($idArtist);
    $albums = $api->getArtistAlbums($idArtist);

    return  array(
      '#theme' => 'spotify_artist',
      '#artist' => $artists->artists[0],
      '#albums' => $albums->items,
      '#cache' => [
       'max-age' => 0,
      ]
    );

  }

  public function getTracks(){

    if(!$this->session->get('accessToken')){
       return new RedirectResponse(\Drupal::url('spotify.login'));
    }

    $idAlbum = \Drupal::request()->query->get('idalbum');
    $api = new SpotifyWebAPI();
    $api->setAccessToken($this->accessToken);
    $tracks = $api->getAlbumTracks($idAlbum);

    return  array(
      '#theme' => 'spotify_tracks',
      '#tracks' => $tracks->items,
      '#cache' => [
       'max-age' => 0,
      ]
    );

  }

}
