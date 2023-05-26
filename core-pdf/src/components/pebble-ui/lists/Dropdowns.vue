<template>
    <div :class="trigger">
        <button class="btn dropdown-toggle" :class="classList()" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            {{selectedValue}}
        </button>

        <ul class="dropdown-menu">
            <li v-for="(list, index) in lists" :key="index">
                <button class="dropdown-item" :class="itemClassList(list)" @click="updateParent(list)">
                    {{list}}
                </button>
            </li>
        </ul>
    </div>
</template>


<script>
export default {
    props: {
        lists: Array,
        currentValue: String,
        displaySize: String,
        outline: {
            type: Boolean,
            default: false
        },
        colorBoostrap: {
            type: String,
            default: "secondary"
        },
        trigger: {
            type: String,
            default: 'dropdown'     // autre possibilité: dropup - dropstart - dropend
        }
    },

    computed : {
        
        /**
         * Retourn la valeur par default
         * Si currentValue exist alors la valeur sera currentValue sinon on prend la premier valeur du tableau
         */
        selectedValue() {
            return this.currentValue ? this.currentValue : this.lists[0];
        },
    },

    methods: {
        /**
         * Retourn la liste de class a ajouter sur le button
         */
        classList() {
            let classList = "";

            let outline = this.outline ? 'outline-' : '';
            classList += " btn-"+ outline + this.colorBoostrap;

            if(this.displaySize) {
                classList += ' btn-'+ this.displaySize;
            }

            return classList;
        },

        /**
         * Retourn la liste de class a ajouter sur les buttons des differents valeur de list envoyée
         * @param {String} list Une valeur du tableau recu
         */
        itemClassList(list) {
            let classList = '';

            if(this.selectedValue == list) {
                classList += 'active';
            }

            return classList;
        },

        /**
         * Retourn a l'element parent la nouvelle valeur sélectionnée
         * @param {String} list Une valeur du tableau lists recuperer
         */
        updateParent(list){
            this.$emit('update-current-value', list);
        }
    },
}
</script>
