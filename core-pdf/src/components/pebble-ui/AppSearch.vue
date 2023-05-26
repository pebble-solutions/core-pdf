<!--
    App search
    Ce composant affiche les outils de recherche et de filtre de la liste.

    Paramètres :
    - filter                    Si true, alors on affiche le bouton de filtre complémentaires
    - action                    Le fichier php interogé en fallback
    - search                    La liste des champs et valeurs recherchées (notamment search.keyword)

    Slots
    - options                   Formulaire de filtres supplémentaires

    Événements émits
    - search                    Événement envoyé au parent lorsque le formulaire est soumis
-->

<template>
    <div>
        <form method="post" class="p-2 border-bottom bg-light sticky-top d-flex" :action="action" @submit.prevent="searchElements()">
            <div class="input-group">
                <!-- v-model ne semble pas tolérer de modifier search.keyword qui hérite d'une propriété -->
                <!--<input type="text" class="form-control" placeholder="Rechercher" v-model="search.keyword">-->
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="btn" :class="filterButtonClass"  @click.prevent="searchOptions = !searchOptions" v-if="filter">
                        <i class="fas fa-filter"></i>
                        <span v-if="hasSearchFilter()">{{hasSearchFilter()}}</span>
                    </button>
                </div>
            </div>
        </form>

        <div id="searchOptions && filter" class="p-2" v-if="searchOptions">

            <slot name="options"></slot>

            <div class="form-group">
                <button class="btn btn-secondary btn-block" @click.prevent="searchElements()">
                    <i class="fas fa-check"></i>
                    Appliquer
                </button>
            </div>
        </div>
    </div>
</template>

<script>

/**
 * Application search component
 *
 * @param {Boolean} filter
 * @param {String} action
 * @param {Object} search
 *
 * @event search {void}
 */
export default {
    props: {
        filter: Boolean,
        action: String,
        search: Object
    },

    data() {
        return {
            searchOptions : false
        }
    },
    
    computed: {
        /**
         * filterButtonClass()
         * Retourne la classe CSS à donner au boutton de filtre. Si des options de recherche sont actives
         * alors le bouton passe en orange.
         * @returns {String}
         */
        filterButtonClass() {
            let type = this.hasSearchFilter() ? 'warning' : 'secondary';
            let outline = this.searchOptions ? '': 'outline-';

            return 'btn-'+outline+type;
        },
        // EO filterButtonClass()
    },

    methods: {
        /**
         * hasSearchFilter()
         * Retourne le nombre de filtres actifs (hors keyword) sur les options de recherche.
         * @returns {Number}
         */
        hasSearchFilter() {
            let i = 0;

            for (let key in this.search) {
                if (this.search[key] && key != 'keyword') {
                    i += 1;
                }
            }

            return i;
        },
        // EO hasSearchFilter()

        /**
         * searchElements()
         * Lance une recherche.
         */
        searchElements() {
            this.searchOptions = false;

            let query = this.search;
            query.q = this.search.keyword;

            this.$root.list(query, 'replace', function(vm) {
                vm.$emit('search');
            });
        },
        // EO searchElements()
    }
}
</script>
