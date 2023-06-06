<!-- 
    Pebble application item
    Ce composant permet d'afficher un item de menu

    Paramètres :
    - module            Configuration du module

    Événement emits
    - launch            module
-->
<template>
    <li class="apps-menu-sidebar-el">
        <router-link :to="item.href" v-slot="{href, navigate, isActive}" custom>
            <a :href="href" @click="navigate" class="apps-menu-sidebar-item text-light rounded" :class="{'active': isActive}" :title="item.label">
                <i :class="item.icon+' app-icon'"></i>
                <span class="app-label">{{item.label}}</span>
            </a>
        </router-link>
    </li>
</template>

<script>

/**
 * Header menu component
 * 
 * @param {Object} item
 */
export default {
    props: {
        item: Object
    }
}

</script>
