<!--
    App search bar
    Ce composant affiche les outils de recherche à effectuer sur une liste.

    Paramètres :
    - showFilter        {Boolean}       Si true, on indique au parent qu'on veux afficher le formulaire filtre
    - searchValue       {String}        Valeur de la recherche, automatiquement mise à jour au parent via un emit
                                        pour effectuer le filtre sur la liste.
    - nbFilterActive    {Number}        Nombre d'élements actif dans le formulaire filtre
    - filterOptions     {Boolean}       Default true. Si true affiche le button de filtre

    Emitter :
    - update:showFilter                 Permet la mise à jour de la valeur showFilter du parent
    - update:searchValue :              Permet la mise à jour de la valeur searchValue du parent

-->

<template>

    <form @submit.prevent="search()">
        <div class="input-group p-2">
            <input  type="text" class="form-control" placeholder="rechercher" v-model="localValue">

            <button class="btn btn-outline-secondary input-group-text" type="submit" @click="search()" :disabled="pending">
                <span class="spinner-border spinner-border-sm" role="status" v-if="pending"></span>
                <i class="bi bi-search" v-else></i>
            </button>

            <button v-if="filterOptions" @click.prevent="toggleFilter()" type="button" class="btn input-group-text" :class="filterButtonClass" >
                <i class="bi bi-funnel-fill"></i> 
                <span v-if="nbFilterActive">{{nbFilterActive}}</span> 
            </button>

        </div>

        <div v-if="showFilter && filterOptions">
            <slot></slot>

            <div class="text-center my-4">
                <button class="btn btn-primary" type="submit" :disabled="pending">
                    <span class="spinner-border spinner-border-sm" role="status" v-if="pending"></span>
                    <i class="bi bi-check-lg" v-else></i>
                    Appliquer
                </button>
            </div>
        </div>
    </form>
    
</template>


<script>

export default {
    name: 'searchBar',

    props: {
        /** v-model props */
        showFilter: Boolean,
        searchValue: String,

        /**basic props */
        nbFilterActive: Number,
        filterOptions: {
            type: Boolean,
            default: true
        },
        pending: Boolean
    },

    data() {
        return {
            localValue: null,
        }
    },

    emits: ['update:showFilter', 'update:searchValue', 'search'],

    computed: {
        /**
         * Retourne la classe CSS à donner au boutton de filtre. Si des options de recherche sont actives
         * alors le bouton passe en orange.
         * @returns {String}
         */
        filterButtonClass() {
            let filter = "";

            if (this.showFilter) {
                filter = "btn-secondary";
            } else {
                filter = "btn-outline-secondary";
            }

            if (this.nbFilterActive) {
                filter = "btn-warning";
            }

            if (this.nbFilterActive && !this.showFilter) {
                filter = "btn-outline-warning";
            }

            return filter;
        }
    },

    watch: {
        /**
         * Envoie la nouvelle valeur à l'élément parent
         * 
         * @param {string} newVal       Nouvelle valeur de recherche
         * 
         * @event update:searchValue
         */
        localValue(newVal) {
            this.$emit('update:searchValue', newVal);
        }
    },

    methods: {
        /**
         * Affiche ou masque les filtres
         * 
         * @event update:showFilter
         */
        toggleFilter() {
            if (this.showFilter) {
                this.search();
            }
            else {
                this.$emit('update:showFilter', !this.showFilter);
            }
        },

        /**
         * Lance la commande de recherche
         * 
         * @event search
         */
        search() {
            this.$emit('update:showFilter', false);
            this.$emit('search');
        }
    },

    mounted() {
        this.localValue = this.searchValue;
    }
}

</script>