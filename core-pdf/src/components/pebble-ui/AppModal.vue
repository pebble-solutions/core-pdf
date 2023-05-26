<!--
    App modal
    Affiche les boites de dialogues pour les applications

    Paramètres :
    - id                        Id unique (sera concaténé avec Modal)
    - title                     Titre de la boite de dialogue
    - cancel-btn                Si true, affiche un bouton Annuler
    - close-btn                 Si true, affiche un bouton Fermer
    - submit-btn                Si true, affiche un bouton Enregistrer
    - delete-btn                Si true, affiche un bouton Enregistrer
    - pending                   Si true, l'enregistrement est en cours
    - pending-delete            Si true, la suppression est en cours

    Événements émits :
    - submit                    Événement envoyé au parent lorsque le formulaire est soumis
    - delete                    Événement envoyé au parent lorsque le boutton suppression est cliqué
-->

<template>
    <div class="modal fade" :id="id+'Modal'" tabindex="-1" :aria-labelledby="id+'ModalLabel'" aria-hidden="true" :data-bs-backdrop="backdrop">
        <div class="modal-dialog" :class="classList">
            <form method="post" @submit.prevent="$emit('submit')" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" :id="id+'ModalLabel'">{{title}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <slot></slot>
                </div>
                <div class="modal-footer" v-if="footer">
                    <button type="button" class="btn btn-outline-danger" v-if="deleteBtn" @click="deleteData()" :disabled="pendingDelete"><i class="bi bi-trash"></i></button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" v-if="cancelBtn">Annuler</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" v-if="closeBtn">Fermer</button>
                    <button type="submit" class="btn btn-primary" :disabled="pending" v-if="submitBtn">
                        <span v-if="pending">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            Enregistrement...
                        </span>
                        <span v-else>{{submitLabel}}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style lang="scss">
@import "@/_variables.scss";

.headerBackgroundTheme {
    background-color: $theme-color;
    color: white;
    box-shadow: $theme-color 0px 15px 20px -15px;
}
</style>

<script>

import * as bootstrap from "bootstrap"

/**
 * Application modal component
 *
 * @param {String} id
 * @param {String} title
 * @param {Boolean} cancelBtn
 * @param {Boolean} closeBtn
 * @param {Boolean} submitBtn
 * @param {Boolean} deleteBtn
 * @param {Boolean} pending
 * @param {String} size         Rien, lg, md, sm
 * @param {Boolean} footer
 * @param {String|Boolean} backdrop
 * @param {Boolean} pendingDelete
 * @param {String} submitLabel
 *
 * @event submit {void}
 * @event delete {void}
 * @event modal-hide {void}
 * @event modal-show {void}
 */
export default {
    props: {
        id: String,
        title: String,
        cancelBtn: Boolean,
        closeBtn: Boolean,
        submitBtn: Boolean,
        deleteBtn: Boolean,
        pending: Boolean,
        pendingDelete: {
            type: Boolean,
            default: false
        },
        display: {
            type: Boolean,
            default: true
        },
        size: {
            type: String,
            default: ''
        },
        className: {
            type: String,
            default: ''
        },
        footer: {
            type: Boolean,
            default: true
        },
        submitLabel : {
            default : "Enregistrer",
            type : String
        },
        backdrop: {
            type: [String, Boolean],
            default: true
        }
    },

    emits: ['modal-show', 'modal-hide', 'submit', 'delete'],

    data() {
        return {
            modal: null,
            display_status: null
        }
    },

    computed: {
        /**
         * Retourne la liste des classes en fonction de la propriété size.
         * Size peut contenir sm, lg et xl
         * 
         * @returns {String}
         */
        classList() {
            let classList = '';
            if (this.size) {
                classList = 'modal-'+this.size;
            }
            
            classList += ' '+this.className;

            return classList;
        }
    },

    watch: {
        /**
         * Le statut display est observé pour afficher ou masquer la boite modale.
         */
        display(val) {
            if (val) {
                this.modal.show();
            }
            else {
                this.modal.hide();
            }
        }
    },
    
    methods: {
        /**
         * deleteData()
         * Lance le signal de suppresion si la boite de dialogue est confirmée
         */
        deleteData() {
            let c = confirm('Souhaitez-vous supprimer cette information ?');
            if (c) {
                this.$emit('delete');
            }
        }
        // EO deleteData()
    },

    /**
     * Avant de retirer le composant, la modale doit être masquée afin de supprimer 
     * tous les éléments html créés par bootstrap
     */
    beforeUnmount() {
        if (this.modal) {
            this.modal.hide();
        }
    },
    
    /**
     * Une fois l'élément monté, on affiche la modale.
     */
    mounted() {
        let modalElement = document.getElementById(this.id+'Modal');
        this.modal = new bootstrap.Modal(modalElement);

        if (this.display) {
            this.modal.show();
        }

        let self = this;

        modalElement.addEventListener('hidden.bs.modal', function () {
            self.$emit('modal-hide');
        });

        modalElement.addEventListener('shown.bs.modal', function () {
            self.$emit('modal-show');
        });
    }
}

</script>
