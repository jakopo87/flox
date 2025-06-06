<template>
  <div class="modal-wrap">

    <div class="modal-header">
      <span class="modal-title">{{ modalData.title }}</span>
      <button class="close-modal" @click="CLOSE_MODAL()">✖</button>
    </div>

    <div class="modal-content modal-content-loading" v-if="loadingModalData">
      <span class="loader fullsize-loader"><i></i></span>
    </div>

    <ol class="season-tabs" v-if=" ! loadingModalData">
      <li v-for="(season, index) in episodes" class="season-number-item">
        <button class="season-number-button" :class="{active: index == seasonActiveModal, completed: seasonCompleted(index)}" @click="seasonNavigation(index)">S{{ addZero(index) }}</button>
      </li>
    </ol>

    <div class="item-header" v-if=" ! loadingModalData">
      <span class="header-name">Name</span>
      <button class="header-seen" @click="toggleAll()" v-if="auth">Toggle all</button>
    </div>

    <ol class="modal-content" v-if=" ! loadingModalData">
      <li v-for="(episode, index) in episodes[seasonActiveModal]"
        class="modal-item-container"
      >
        <label
             class="modal-item"
             :data-episode="episode.episode_number"
        >
          <span class="modal-episode">E{{ addZero(episode.episode_number) }}</span>
          <span class="modal-name" :class="{'spoiler-protect': spoiler && ! episode.seen}">{{ episode.name }}</span>
          <i class="item-has-src" v-if="episode.src"></i>
          <span class="modal-release-episode" v-if="episode.release_episode"><i></i> {{ releasedDate(episode.release_episode) }}</span>
          <span class="modal-release-episode" v-if=" ! episode.release_episode"><i></i> {{ lang('no release') }}</span>
          <input type="checkbox" class="episode-seen" @change="toggleEpisode(episode)" :checked="episode.seen">
        </label>
      </li>
    </ol>

  </div>
</template>

<script>
  import { mapState, mapMutations } from 'vuex';
  import http from 'axios';

  import MiscHelper from '../../helpers/misc';
  import ItemHelper from '../../helpers/item';

  export default {
    mixins: [MiscHelper, ItemHelper],

    data() {
      return {
        auth: config.auth
      }
    },

    computed: {
      ...mapState({
        modalData: state => state.modalData,
        loadingModalData: state => state.loadingModalData,
        seasonActiveModal: state => state.seasonActiveModal
      }),

      episodes() {
        return this.modalData.episodes;
      },

      spoiler() {
        return this.modalData.spoiler;
      }
    },

    methods: {
      ...mapMutations([ 'SET_SEASON_ACTIVE_MODAL', 'CLOSE_MODAL', 'SET_LOADING_MODAL_DATA', 'SET_MODAL_DATA' ]),

      releasedDate(date) {
        const released = new Date(date * 1000);

        return this.formatLocaleDate(released);
      },

      seasonNavigation(index) {
        this.SET_SEASON_ACTIVE_MODAL(index)

        const episodesListContainer = document.querySelector('.modal-content');
        episodesListContainer.scrollTop = 0;
      },

      toggleAll() {
        const season = this.seasonActiveModal;
        const tmdb_id = this.modalData.episodes[1][0].tmdb_id;
        const seen = this.seasonCompleted(season);

        this.markAllEpisodes(season, seen);

        http.patch(`${config.api}/toggle-season`, {
          tmdb_id,
          season,
          seen: ! seen
        });
      },

      markAllEpisodes(season, seen) {
        const episodes = this.episodes[season];

        for(let episode of episodes) {
          episode.seen = ! seen;
        }
      },

      toggleEpisode(episode) {
        if(this.auth) {
          episode.seen = ! episode.seen;

          http.patch(`${config.api}/toggle-episode/${episode.id}`).catch(error => {
            episode.seen = ! episode.seen;
          });
        }
      },

      seasonCompleted(index) {
        const episodes = this.episodes[index];

        for(let episode of episodes) {
          if( ! episode.seen) {
            return false;
          }
        }

        return true;
      }
    }
  }
</script>
