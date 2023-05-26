<template>
    <div class="modal fade" id="loginModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loginModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mb-4 mt-2">
                        <h3 class="modal-title" id="loginModalTitle">Connexion</h3>
                    </div>

                    <UserImage :name="userDisplayName" class-name="user-image-lg" class-name-username="lead" display="full" :image-url="user.photoURL" v-if="user" />

                    <AlertMessage v-if="error" icon="bi bi-exclamation-circle-fill" variant="danger">{{error}}</AlertMessage>

                    <LicenceSelector :licences="licences" v-if="view == 'licence'" @error="setError" />

                    <LoginForm :display-error="false" @error="setError" v-else />

                    <div class="text-center mt-3 pt-3 mb-2">
                        <img src="@/components/pebble-ui/assets/pebble-dark-64.png" alt="Pebble logo" title="Pebble V" class="pebble-logo">
                        <div v-if="env != 'prod'">
                            <code>Environnement {{env}}</code><br>
                            <code v-if="apiConfig">Pebble Authenticator sur {{apiConfig.authServer}}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

import * as bootstrap from "bootstrap";
import LoginForm from "./LoginForm.vue";
import LicenceSelector from "../licence/LicenceSelector.vue";
import UserImage from "../UserImage.vue";
import AlertMessage from "../AlertMessage.vue";
// import { LicenceNotFoundError } from "../../../js/app/errors";

/**
 * Ce composant affiche une boite de dialogue afin de se connecter à un compte utilisateur.
 * Lorsque la connexion est valide, le composant renvoie l'utilisateur connecté et passe 
 * automatiquement sur la structure par défaut de l'utilisateur.
 * 
 * @param {Boolean} display Affiche ou masque la boite de dialogue
 */
export default {
    props: {
        display: Boolean
    },

    data() {
        return {
            modal: null,
            licences: [],
            view: 'form',
            user: null,
            error: null,
            env: null,
            apiConfig: null
        };
    },

    computed: {
        /**
         * Retourne un display name pour l'utilisateur actif en fonction des informations displayName et email
         * @returns {String}
         */
        userDisplayName() {
            if (this.user) return this.user.displayName ? this.user.displayName : this.user.email.split('@')[0];
            else return null;
        }
    },

    methods: {
        /**
         * Affecte un message d'erreur à variable error
         * @param {String} error Le message d'erreur
         */
        setError(error) {
            this.error = error;
        },

        /**
         * Initialise la vue en mode formulaire de connexion ou sélection de licence.
         */
        initView() {
            if (this.user && this.licences.length) {
                this.view = 'licence';
            }
            else {
                this.view = 'form';
            }

            // if (this.user && !this.licences.length) {
            //     let er = new LicenceNotFoundError();
            //     this.error = er.message;
            // }
            // else {
            //     this.setError(null);
            // }
        }
    },

    beforeUnmount() {
        this.modal.hide();
    },

    mounted() {
        this.modal = new bootstrap.Modal(document.getElementById("loginModal"));
        this.modal.show();

        this.user = this.$app.firebase_user;
        this.licences = this.$app.licences ? this.$app.licences : [];

        this.initView();
        
        this.$app.addEventListener("licencesRetrieved", (licences) => {
            this.user = this.$app.firebase_user;
            this.licences = licences;
            this.initView();
        });

        this.$app.addEventListener("authInited", (user) => {
            this.user = user;
            this.initView();
        });

        this.$app.addEventListener("authCleared", () => {
            this.user = null;
            this.licences = [];
            this.initView();
        });

        this.$app.addEventListener("authError", (message) => {
            this.error = message;
        });

        this.env = this.$app.env;
        this.apiConfig = this.$app.apiConfig;
    },

    components: { LoginForm, LicenceSelector, UserImage, AlertMessage }
}
</script>

<style lang="scss" scoped>
.pebble-logo {
    max-width: 32px;
}
</style>