<!--
    App wrapper
    Ce composant affiche la structure générale de l'application.

    Paramètres :
    - cfg               Paramètre de configuration de l'application
    - cfgMenu           Paramètre du menu de configuration. Si une clé href existe, alors le menu de configuration s'affiche
    - cfgSlots          Paramètre d'affichage des slots (éléments)

    Événement emits
    - menu-toggle       Envoie le signal d'inversion du menu de l'application
    - config-module     Envoie le signal d'affichage de la boite de dialogue de configuration
    - auth-change       Mise à jour de l'authentification de l'utilisateur
-->

<template>
    <div class="row g-0 bg-dark sticky-top text-light align-items-center border-bottom border-secondary" :class="{'filter-blur' : !local_user && cfg.ppp !== 'public'}" id="app-header" style="z-index: 1025;">
        <AppHeaderMenu
            :cfg="cfg"
            :cfg-menu="cfgMenu"
            :cfg-slots="cfgSlots"
            :local_user="local_user"
            :sidebar-menu="sidebarMenu"

            @update-sidebar="updateSidebar"
            @menu-toggle="menu = !menu"
            @config-module="$emit('config-menu')" />

        <div class="col d-flex align-items-center justify-content-between" :class="classHeader">
            <div>
                <slot name="header" v-if="slots.header"></slot>
            </div>

            <!-- Barre d'outil ITCloud -->
            <div>
                <AppHeaderUserMenu
                    :cfg-menu="cfgMenu"
                    :cfg="cfg"
                    :local_user="local_user"
                    :licence="licence"
                    :active_structure_id="active_structure_id"

                    @config-module="$emit('config-menu')"
                    @storage-modal="storageModal = true"
                    @licence-modal="licenceModal = true"
                    @structure-change="activateStructure"
                    @user-modal="userModal = true" />
            </div>
            <!-- Fin de la barre d'outil IT Cloud -->
        </div>
    </div>

    <div class="row g-0" :class="{'filter-blur' : !local_user && cfg.ppp !== 'public'}" :style="'padding-left:'+paddingLeft+';'">
        <div class="col border-end overflow-auto scrollbar sidebar-full-height sticky-top pebble-aside-menu shadow-sm" :class="{'menu-opened': menu, 'bg-light': menuMode == 'list', 'bg-dark text-light': menuMode == 'menu'}" id="app-list"  v-if="slots.menu || slots.list">
            <div class="btn-group rounded-0 w-100 p-1 sticky-top shadow-sm border-bottom" v-if="slots.menu && slots.list  && cfg.app_mode != 'standalone'" :class="{'bg-light border-light': menuMode == 'list', 'bg-dark border-dark': menuMode == 'menu'}">
                <input type="radio" class="btn-check" name="menuMode" id="menuMode-menu" autocomplete="off" v-model="menuMode" value="menu">
                <label class="btn btn-outline-custom w-50" for="menuMode-menu">
                    <i class="bi bi-three-dots-vertical"></i>
                    Menu
                </label>

                <input type="radio" class="btn-check" name="menuMode" id="menuMode-list" autocomplete="off" v-model="menuMode" value="list">
                <label class="btn btn-outline-custom w-50" for="menuMode-list">
                    <i class="bi bi-list-ul"></i>
                    Liste
                </label>
            </div>

            <slot name="menu" v-if="menuMode == 'menu' && slots.menu && cfg.app_mode != 'standalone'"></slot>
            <slot name="list" v-else-if="menuMode == 'list' || slots.list"></slot>
        </div>

        <div class="col" :class="classCore" v-if="slots.core">
            <slot name="core"></slot>

            <div class="app-footer" id="app-footer">
                <div class="mb-2 fs-7">
                    <img src="@/components/pebble-ui/assets/pebble-dark-64.png" title="Pebble: Work less, do more" alt="Pebble" style="max-width: 48px;">
                    <div class="text-secondary my-1">{{ appName }} {{ appVersion }}</div>
                    <div v-if="env != 'prod'"><code>Environnement {{env}}</code></div>
                </div>
            </div>
        </div>
    </div>

    <LoginModal v-if="!local_user && !pending.initAuth && cfg.ppp !== 'public'" />

    <StorageModal :display="storageModal" @modal-hide="storageModal = false" @modal-show="storageModal = true" v-if="cfg.ppp == 'private'" />

    <AppModal v-if="licenceModal" :display="true" :footer="false" title="Licence" id="licenceOverview" @modal-hide="licenceModal = false">
        <LicenceOverview :licence="licence" />
    </AppModal>

    <AppModal title="Mon compte" v-if="userModal" :display="true" :footer="false" id="accountOverview" @modal-hide="userModal = false">
        <UserForm />
    </AppModal>
</template>

<script>

import AppHeaderMenu from './AppHeaderMenu.vue'
import AppHeaderUserMenu from './AppHeaderUserMenu.vue'
import LoginModal from './login/LoginModal.vue'
import StorageModal from './StorageModal.vue'
import AppModal from './AppModal.vue'
import LicenceOverview from './licence/LicenceOverview.vue'
import UserForm from './user/UserForm.vue'
import packageInfo from '../../../package.json'

/**
 * Application wrapper component
 *
 * @param {Object} cfg
 * @param {Object} cfgMenu
 * @param {Object} cfgSlots
 * @param {Array} sidebarMenu
 *
 * @event menu-toggle {Boolean}
 * @event config-module {Void}
 * @event auth-change {Object}
 */

export default {
    props: {
        cfg: Object,
        cfgMenu: Object,
        cfgSlots: {
            type: Object,
            default() {
                return {
                    menu: true,
                    list: true,
                    core: true,
                    header: true
                }
            }
        },
        sidebarMenu: Array
    },

    emits: ['auth-change', 'menu-toggle', 'config-module', 'structure-change', 'config-menu'],

    data() {
        return {
            pending: {
                structures: true,
                sessLogin: true,
                initAuth: true
            },
            menu: false,
            menuMode : 'list',
            slots: {
                menu: true,
                list: true,
                core: true,
                header: true
            },
            local_user: null,
            active_structure_id: null,
            storageModal: false,
            licenceModal: false,
            licence: null,
            isMobile : false,
            env: null,
            userModal: false
        }
    },

    components: { AppHeaderMenu, AppHeaderUserMenu, LoginModal, StorageModal, AppModal, LicenceOverview, UserForm },

    watch: {
        /**
         * menu(val)
         * Observe le changement d'état du menu
         * @param {Bool} val
         *
         * @event menu-toggle {Bool}
         */
        menu(val) {
            this.$emit('menu-toggle', val);
        },
        // EO menu()

    },

    computed: {
        /**
         * paddingLeft()
         * Retourner l'espace à gauche si aside est à true
         * @returns {String}
         */
        paddingLeft() {
            if (this.cfg.aside && !this.isMobile) {
                return '52px';
            }
            else {
                return '0px';
            }
        },
        
        /**
         * Retourne la classe pour l'élément d'interface "core"
         */
        classCore() {
            return this.getClassName('core');
        },

        /**
         * Retourne la classe pour l'élément d'interface "sidebar"
         */
        classSidebar() {
            return this.getClassName('sidebar');
        },

        /**
         * Retourne la classe pour l'élément d'interface "header"
         */
        classHeader() {
            return this.getClassName('header');
        },

        /**
         * Retourne les options complémentaires de configuration des slots
         */
        slotsOptions() {
            return this.cfgSlots.options ? this.cfgSlots.options : {};
        },

        /**
         * Retourne le nom de l'application déclarée dans package.json
         * @return {string}
         */
        appName() {
            return packageInfo.name;
        },

        /**
         * Retourne la version de l'application déclarée dans package.json
         * @return {string}
         */
        appVersion() {
            return packageInfo.version;
        }
    },

    methods: {
        /**
         * resize()
         * This function calculate the size of interface element on application load,
         * view update or browser resize
         */
        resize() {
            let header = document.getElementById('app-header');
            let aside = document.getElementById('app-list');

            let win_height = window.innerHeight;
            let header_height = header.offsetHeight;

            let height = win_height - header_height;

            if (aside) {
                aside.style.height = height+'px';
                aside.style.top = header_height+'px';
            }
        },

        /**
         * Met à jour l'utilisateur local
         * @param {Object} user
         */
        setLocal_user(user) {
            this.local_user = user;
            this.$emit('auth-change', user);
        },

        /**
         * Met à jour la structure active
         * @param {Integer} structureId
         */
        setActiveStructureId(structureId) {
            this.active_structure_id = structureId;
            this.$emit('structure-change', structureId);
        },

        /**
         * Demande de changer de structure
         * @param {Integer} structureId
         */
        activateStructure(structureId) {
            this.$app.setStructure(structureId);
            this.setActiveStructureId(structureId);
        },

        /**
         * Met a jour la variable isMobile a true si la taille est une taille mobile,
         * défini dans AppHeaderMenu
         * @param {Boolean} isMobile 
         */
        updateSidebar(isMobile) {
            this.isMobile = isMobile;
        },

        /**
         * Retourne le nom de la classe CSS pour un slot donné depuis la configuration.
         * @param {String} slot Le nom du slot
         * @returns {String}
         */
        getClassName(slot) {
            if (this.slotsOptions[slot]) {
                return this.slotsOptions[slot].className ? this.slotsOptions[slot].className : '';
            }
            return '';
        }
    },
    
    mounted() {
        for (const key in this.cfgSlots) {
            this.slots[key] = this.cfgSlots[key];
        }

        if(!this.cfgSlots.menu && this.cfgSlots.list) {
            this.menuMode = 'list';
        }

        if (this.cfg.menuMode) {
            this.menuMode = this.cfg.menuMode;
        }

        this.resize();

        window.addEventListener('resize', () => {
            this.resize();
        });

        this.$app.addEventListener('licenceCleared', () => {
            this.setLocal_user(null);
            this.setActiveStructureId(null);
            this.$router.push('/');
        });

        this.$app.addEventListener('authRefreshed', (user) => {
            this.setLocal_user(user);
        });

        this.$app.addEventListener('authChanged', (user) => {
            this.setLocal_user(user);
        });

        this.$app.addEventListener('structureChanged', (structureId) => {
            this.setActiveStructureId(structureId);
        });

        this.$app.addEventListener('menuChanged', (status) => {
            this.menuMode = status;
        });

        this.$app.addEventListener('licenceChanged', (licence) => {
            this.licence = licence;
        })

        this.$app.addEventListener('authInited', () => {
            this.pending.initAuth = false;
        });

        this.$app.addEventListener('authError', () => {
            this.pending.initAuth = false;
        });

        this.$app.checkAuth();

        this.env = this.$app.env;

        document.title = this.$app.cfg.moduleLabel+' - Pebble';

    }
};
</script>


<style lang="scss">

@use "sass:color";
@import "@/_variables.scss";

.apps-menu-btn {
    padding:10px 12px;
    background-color: $theme-color;
}

.apps-menu-btn:hover {
    background-color: darken($theme-color, 10%);
}

.btn-outline-custom {
    color: $theme-color!important;
    border-color: $theme-color!important;
}

.btn-outline-custom:hover {
    color:white!important;
    background-color: $theme-color!important;
}

.btn-check:checked+.btn-outline-custom {
    color: white!important;
    background-color: $theme-color!important;
}



.apps-menu-icon {
    display: inline-block;
    min-width:28px;
    min-height:28px;
    vertical-align: middle;
    /*background:#ffffff;
    mask-size:cover;
    -webkit-mask-size:cover;*/
}

.apps-menu-icon-apps {
    /*mask: url('/mkg/images/svg/application-mobile.svg');
    -webkit-mask: url('/mkg/images/svg/application-mobile.svg');*/
    background-image:url('assets/pebble-white-64.png');
    background-position: center;
    background-size: contain;
    background-repeat: no-repeat;
}

.apps-menu-icon-back {
    // mask: url('/mkg/images/svg/left-arrow.svg');
    // -webkit-mask: url('/mkg/images/svg/left-arrow.svg');
    background:#ffffff;
    mask-size:cover;
    -webkit-mask-size:cover;
}

.apps-menu-icon-bi {
    line-height:28px;
    text-align: center;
}

.apps-menu-sidebar {
    position:fixed; 
    left:0px; 
    width:52px; 
    bottom:0px; 
    top:48px;
    border-top:1px solid darken($theme-color, 8%);
    transition: width .3s, top .1s;
    z-index: 1030;
    background-color:$theme-color;
}

.apps-menu-sidebar .app-label {
    display:none;
}

.apps-menu-sidebar.active {
    width:100%;
    top:0px;
    border-top-width: 0px;
    overflow: auto;
}

.apps-menu-sidebar.active .app-label {
    display:inline;
}

.apps-menu-sidebar-logo {
    position:fixed;
    bottom:0px;
    left:0px;
    width:52px;
    box-sizing: border-box;
    padding:15px;
}

.apps-menu-sidebar-items {
    margin:10px 0px;
    padding:0px;
}

.apps-menu-sidebar-items + .apps-menu-sidebar-items {
    border-top:1px solid rgba(0,0,0,0.2);
    padding-top:10px;
}

.apps-menu-border {
    border-color: rgba(0,0,0,0.2) !important;
}

.apps-menu-sidebar-items.horizontal {
    display:flex;
    flex-wrap: wrap;
    align-items: flex-start;
}

.apps-menu-sidebar-items.horizontal .apps-menu-sidebar-el {
    min-width:90px;
    max-width:130px;
    flex:1;
    text-align: center;
}

.apps-menu-sidebar-items.horizontal .app-icon,
.apps-menu-sidebar-items.horizontal .app-label {
    display:block;
    margin:8px auto;
}

.apps-menu-sidebar-el {
    padding:0px;
    margin:0px;
    list-style-type: none;
}

.apps-menu-sidebar-item {
    display:flex;
    padding:10px 0px;
    text-decoration: none;
    text-transform: capitalize;
}

.apps-menu-sidebar-item:hover {
    text-decoration: none;
}

.apps-menu-sidebar-item .app-icon {
    text-align: center;
    width:52px;
}

.apps-menu-sidebar-item:hover,
.apps-menu-sidebar-item.active {
    background-color: rgba(0,0,0,0.1);
}

.apps-launcher {
    position: fixed;
    left:380px;
    right:0px;
    bottom:0px;
    top:48px;
}

.pebble-aside-menu {
    display: none;
}

.pebble-aside-menu.menu-opened {
    display: block;
    position: fixed;
    top : 52px;
    bottom : 0px;
    width: 100%!important;
    max-width: 260px;
    z-index:1100;
}

.pebble-cursor-pointer {
    cursor: pointer;
}

@media (min-width: 1024px) {
    .pebble-aside-menu {
        display: block;
        width: 350px!important;
        flex: none!important;
    }

    .pebble-aside-menu.menu-opened {
        display: block;
        position: sticky;
        top: auto; 
        bottom: auto;
        width: 25%;
        
    }
}


/**
*   Style des élements d'administration
*/
.text-admin {
    color:#a012ff;
}

.text-bg-admin {
    background-color:#a012ff;
    color:white;
}

.btn-admin {
    background-color: #a012ff !important;
    border-color: #6f09b3 !important;
    color:white !important;
}

.btn-admin:hover {
    background-color: #8a10db !important;
    border-color: #60099b !important;
    color:white !important;
}

.btn-outline-admin {
    border-color: #a012ff !important;
    color:#a012ff !important;
}

.btn-outline-admin:hover {
    border-color: #a012ff !important;
    color:white !important;
    background-color: #8a10db !important;
}

.btn-admin:focus,
.btn-outline-admin:focus {
    box-shadow: 0 0 0 0.2rem rgba(160, 18, 255, 0.5) !important;
}

.app-footer {
    opacity:0.5;
    margin:auto;
    padding:20px 0px;
    text-align: center;
    transition: opacity .1s;
}

.app-footer:hover {
    opacity:0.6;
}

.alert-admin {
    color:#655074;
    background-color: #f2e9f7;
    border-color: #b49cc4;
}

/**
 * Dropzone style
 */

 .dropzone-wrapper {
    margin:15px 0px;
 }

.dropzone {
	border: 2px dashed rgb(212, 212, 212);
	color:rgb(100,100,100);
	border-radius: 5px;
	background-color: rgb(250,250,250);
    min-height: auto;
    padding:8px 15px;
    margin-bottom:4px;
}

.dropzone .dz-message {
    margin:0px;
}

.dropzone:hover {
	background-color:rgb(245,245,245);
	border-color: rgb(180,180,180);
}

.dropzone-dark {
	background-color:rgb(80,80,80);
	border-color: rgb(50,50,50);
	color:rgb(150,150,150);
}

.dropzone-dark:hover {
	background-color:rgb(70,70,70);
	border-color: rgb(40,40,40);
}

.filter-blur {
    filter: blur(8px);
    -webkit-filter: blur(8px);
}
</style>