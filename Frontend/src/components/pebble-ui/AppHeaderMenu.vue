<template>
    <div class="col-3 border-secondary d-flex align-items-center pebble-rod-menu" :class="{'border-end' : cfgSlots.menu || cfgSlots.list}" :style="'max-width:'+mobileMenuSize+';'">
        <a class="apps-menu-btn me-3" href="#!" title="Retour" v-if="(cfg.app_mode == 'standalone' || cfg.backNavigation) && !navIndex" @click.prevent="actionBack()">
            <span class="apps-menu-icon apps-menu-icon-bi"><i class="bi bi-arrow-left text-white"></i></span>
        </a>

        <span class="apps-menu-btn me-3" title="Toutes les applications" v-else>
            <i v-if="cfg.logos" class="apps-menu-icon"><img :src="getLogo()" alt="Pebble"></i>
            <i v-else class="apps-menu-icon apps-menu-icon-apps"></i>
        </span>

        <!-- <a class="apps-menu-btn me-3" href="/admin" title="Toutes les applications" @click.prevent="appsLauncher = !appsLauncher" v-else-if="cfg.app_mode != 'standalone' && cfg.ppp == 'private'">
            <i v-if="cfg.logos" class="apps-menu-icon"><img :src="getLogo()" alt="Pebble"></i>
            <i v-else class="apps-menu-icon apps-menu-icon-apps"></i>
        </a> -->



        <a href="#!" class="navbar-brand text-light " v-if="burgerMenu" @click.prevent="menu = !menu" title="Menu application">
            <i class="bi bi-x-lg me-1" style="width:22px; text-align: center;" v-if="menu"></i>
            <i class="bi bi-list me-1" style="width:22px; text-align: center;" v-else></i>
            {{cfg.moduleLabel}}
        </a>
        <span class="navbar-brand text-light " v-else>
            {{cfg.moduleLabel}}
        </span>

        <div class="apps-menu-sidebar" v-if="cfg.aside && !isMobile">

            <ul class="apps-menu-sidebar-items" v-if="sidebarMenu">
                <AppHeaderMenuItem v-for="item in sidebarMenu" :item="item" :key="item.key" />
            </ul>

            <div class="apps-menu-sidebar-logo">
                <img src="@/components/pebble-ui/assets/pebble-text-v-white.png" class="img-fluid" alt="Pebble">
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
    .pebble-rod-menu {
        width: 100%!important;
        max-width: 350px;
    }

    .pebble-burger-menu {
        display: block;
    }

    @media (min-width: 1024px) {
        .pebble-rod-menu {
            width: initial;
        }

        .pebble-burger-menu {
            display : none;
        }
    }

</style>

<script>

import AppHeaderMenuItem from './AppHeaderMenuItem.vue'

/**
 * Header menu component
 * 
 * @param {Object} cfg
 * @param {Object} cfgMenu
 * @param {Object} cfgSlots
 * @param {Object} localUser
 * @param {Array} sidebarMenu
 * 
 * @event {Boolean} menu-toggle
 * @event {Void} config-module
 */
export default {
    props: {
        cfg: Object,
        cfgMenu: Object,
        cfgSlots: Object,
        local_user: Object,
        sidebarMenu: Array
    },

    data() {
        return {
            menu: false,
            pending: {
                modules: true
            },
            winWidth: 0
        }
    },    
    watch: {
        /**
         * Contrôle l'affichage du menu
         * @param {bool} val 
         */
        menu(val) {

            this.$emit('menu-toggle', val);

        }
    },

    computed: {

        /**
         * aside : retourne dans les cas suivants
         * - cfg.aside == true
         * - typeof cfg.aside == undefinded
         */
        aside() {
            if (this.cfg.aside || typeof this.cfg.aside === 'undefined') {
                return true;
            }
            return false;
        },
        // EO aside

        /**
         * retourn true si l'ecran est inferieur a 1024px et que Menu ou List est a true
         * @returns {Boolean}
         */
        burgerMenu() {
            if(this.winWidth < 1024 && (this.cfgSlots.menu || this.cfgSlots.list)) {
                return true;
            }
            return false;
        },

        /**
         * paddingLeft()
         * Retourner l'espace à gauche si aside est à true
         * @returns {String}
         */
        mobileMenuSize() {
            if(this.winWidth < 1024) {
                if(this.cfg.aside && !this.isMobile) {
                    return '312px';
                }
                return '260px';
            } else {
                if(this.cfg.aside && !this.isMobile) {
                    return '402px';
                } else {
                    return '350px';
                }
            }

            //return '350px';
        },
        // EO paddingLeft()

        /**
         * Retourn true si la taille correspond a une taille mobile
         */
        isMobile() {
            if(this.winWidth > 578) {
                return false;
            }

            return true;
        },

        /**
         * Retourne true si la route active est l'index
         * @returns {Boolean}
         */
        navIndex() {
            if (this.cfg.backAction) {
                if (this.cfg.backAction !== this.$route.path) {
                    return false;
                }
            }

            return true;
        }


    },

    components: {
        AppHeaderMenuItem
    },

    methods: {
        /**
         * closeWindow()
         * Ferme l'onglet actif
         */
        closeWindow() {
            window.close();
        },
        // EO closeWindow()

        /**
         * Lance l'action de retour lors du clique sur la flèche retour
         */
        actionBack() {
            if (this.cfg.backAction) {
                this.$router.push(this.cfg.backAction);
            }
            else {
                this.closeWindow();
            }
        },

        /**
         * Retourne le chemin vers le logo de l'application.
         * Si cfg.logos.small existe, alors, c'est ce chemin qui sera utilisé. Dans le cas contraire, c'est cfg.logos.default
         * qui sera retourné
         * 
         * @returns {String} Le chemin vers l'image
         */
        getLogo() {
            if(this.cfg.logos.small) {
                return this.cfg.logos.small;
            } else if(this.cfg.logos.default) {
                return this.cfg.logos.default;
            } else {
                return '';
            }
        }
    },

    mounted() {
        this.winWidth = window.innerWidth;

        window.addEventListener('resize', () => {
            this.winWidth = window.innerWidth;

            this.$emit('update-sidebar', this.isMobile);
        });

        this.$emit('update-sidebar', this.isMobile);
    }
}
</script>
