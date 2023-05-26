<!--
    Pebble menu utilisateur
    Ce composant affiche le menu utilisateur, le menu des structures et le paramétrage général

    Paramètres :
    - structures        Liste des structures chargées par l'application
    - sessLogin         Login connecté
    - cfgMenu           Paramètre du menu de configuration. Si une clé href existe, alors le menu de configuration s'affiche

    Événement emits
    - config-module     Envoie le signal d'affichage de la boite de dialogue de configuration
-->

<template>
    <ul class="nav align-items-center mx-2">
        <li class="nav-item dropdown structure-menu-options-tools" v-if="local_user && cfg.ppp == 'private'">
            <a href="#" title="Changer de structure" class="nav-link text-light dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" v-if="cfg.ppp == 'private'"><i class="fa fa-sitemap"></i> {{activeStructure.nom_interne}}</a>
            <div class="dropdown-menu dropdown-menu-right">
                <AppStructureMenuItem :active_structure_id="active_structure_id" v-for="structure in structures" :key="'structure-'+structure.id" :structure="structure" @select-structure="setActiveStructure" />
            </div>
        </li>
        <li class="nav-item dropdown" v-if="local_user && (cfg.ppp == 'private' || cfg.ppp == 'partner')">
            <a href="{RACINE_VERSION}modules/espace/private/php/menu_profile.php" :title="'@'+local_user.login.pseudo" class="d-block text-decoration-none" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                <UserImage :name="local_user.login.pseudo" />
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <h2 class="dropdown-header">{{local_user.login.pseudo}}</h2>
                <!-- <a class="dropdown-item" href="/mkg/modules/espace/private/php/login_3_resume.php?login_id={sess_login_id}" target="espaceApp">Mon compte</a> -->
                <a class="dropdown-item" @click.prevent="userModal()" href="#!">Mon compte</a>
                <a class="dropdown-item" @click.prevent="logout()" href="#!">Déconnexion</a>

                <div class="user-menu-options-tools" v-if="cfg.ppp == 'private'">
                    <hr class="dropdown-divider">
                    <h2 class="dropdown-header">Structure</h2>
                    <AppStructureMenuItem :active_structure_id="active_structure_id" v-for="structure in structures" :key="'structure-sub-'+structure.id" :structure="structure" @select-structure="setActiveStructure" />
                </div>

                <hr class="dropdown-divider">
                <h2 class="dropdown-header">Licence</h2>
                <button class="dropdown-item" @click.prevent="$emit('licence-modal')">{{licence.computedName}}</button>
                <button class="dropdown-item" @click.prevent="switchLicence()">Autres licences</button>

                <div class="user-menu-options-tools" v-if="cfg.ppp == 'private'">
                    <hr class="dropdown-divider">
                    <h2 class="dropdown-header">Options</h2>
                    <AppOptionsMenu :cfg-menu="cfgMenu" @config-module="configModule()" @storage-modal="storageModal()" />
                </div>

            </div>
        </li>
        <li class="nav-item dropdown toolbar-menu-options-tools" v-if="cfg.ppp == 'private'">
            <a href="#" class="nav-link text-light"  data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-gear"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <AppOptionsMenu :cfg-menu="cfgMenu" @config-module="configModule()" @storage-modal="storageModal()" />
            </div>
        </li>
    </ul>

</template>

<style scoped>

.user-menu-options-tools {
    display: block;
}

.toolbar-menu-options-tools,
.structure-menu-options-tools {
    display:none;
}

@media (min-width: 1024px) {
    .user-menu-options-tools {
        display: none;
    }

    .toolbar-menu-options-tools,
    .structure-menu-options-tools {
        display:initial;
    }
}

</style>

<script>

import UserImage from './UserImage.vue'
import AppOptionsMenu from './AppOptionsMenu.vue';
import AppStructureMenuItem from './AppStructureMenuItem.vue';


/**
 * Header menu component
 * 
 * @param {Object} cfgMenu
 * @param {Object} cfg
 * @param {Object} local_user
 * @param {Number} active_structure_id
 * @param {Object} licence
 * 
 * @event {Void} config-module
 * @event {Void} storage-modal
 * @event {Integer} structure-change
 */
export default {
    data() {
        return {
			displayModal: false
        };
    },
    props: {
        local_user: Object,
        active_structure_id: Number,
        cfgMenu: Object,
        cfg: Object,
        licence: Object
    },

    emits: ['storage-modal', 'config-module', 'structure-change', 'licence-modal'],

    components: { UserImage, AppOptionsMenu, AppStructureMenuItem },
    

    computed: {
        /**
         * Retourne l'objet de la structure active
         * @returns {Object}
         */
        activeStructure() {
            return this.local_user.structures.find(e => e.id === this.active_structure_id);
        },

        /**
         * Retourne la liste des structures du local_user
         * @return {Array}
         */
        structures() {
            let structures = [];

            if (this.local_user) {
                structures = this.local_user.structures;
            }

            return typeof structures === 'object' ? structures : [];
        }
    },
    
    methods: {
        /**
         * Envoie un événement 'config-module' à l'élément parent
         */
        configModule() {
            this.$emit('config-module');
        },

        /**
         * Envoie un événement 'storage-modal' à l'élément parent
         */
        storageModal() {
            this.$emit('storage-modal');
        },

        /**
         * Envoie un événement pour modifier la structure
         * @param {Interger} structureId
         */
        setActiveStructure(structureId) {
            this.$emit('structure-change', structureId);
        },

        /**
         * Ferme la session
         */
        logout() {
            this.$app.logout();
        },

        /**
         * Vérifie que l'utilisateur dispose de plusieurs licences pour cette application. Dans ce
         * cas, vide l'auth afin d'afficher la modal de sélection des licences. Dans le cas contraire,
         * affiche une notification
         */
        switchLicence() {
            this.$app.getLicences()
            .then(licences => {
                if (licences.length > 1) {
                    this.$app.clearLicence();
                }
                else {
                    alert("Vous ne disposez pas d'autres licences pour ce compte");
                }
            })
        },

        /**
         * Envoie un événement 'user-modal' pour afficher le formulaire de modification 
         * de l'utilisateur connecté
         */
        userModal() {
            this.$emit('user-modal');
        }
    }
}
</script>
