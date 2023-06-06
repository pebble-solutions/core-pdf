<!--
    App menu item
    Ce composant gère l'affichage des éléments du menu, stockés dans app-menu

    Paramètres :
    - href                      Le lien dans le menu
    - active                    True ou False si l'item est actif ou inactif
    - view                      Le nom de la vue (facultatif)
    - icon                      L'icon du menu (facultatif)
    - style                     light (par defaut) ou dark

    Slots :
    - default                   Contenu du libellé
-->
<template>
    <router-link :to="href" v-slot="{href, navigate, isActive}" custom v-if="href">
        <a :href="href" @click="navigate" class="list-group-item list-group-item-action" :class="{'active': isActive, 'bg-dark border-secondary': !isActive && look == 'dark', 'text-light' : look == 'dark'}">
            <i class="me-2" :class="icon" style="width:16px; text-align:center;" v-if="icon"></i>
            <slot></slot>
        </a>
    </router-link>

    <button type="button" class="btn rounded-0" :class="buttonStyle" v-else>
        <slot></slot>
    </button>
</template>

<script>

/**
 * Application menu item component
 *
 * @param {String} href
 * @param {Boolean} active
 * @param {String} view
 * @param {String} icon
 * @param {String} look
 */
export default {
    props: {
        href: String,
        active: Boolean,
        view: String,
        icon: String,
        look: String,
        buttonStyle : String
    }
}
</script>
