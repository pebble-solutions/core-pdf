<template>
    <select class="form-select" :class="classList()">
        <option v-for="(list, index) in lists" :key="index" :value="list" @click="updateParent(list)" :selected="selectedValue == list ? true : false">{{list}}</option>
    </select>
</template>>

<script>
export default {
    props: {
        lists: Array,
        currentValue: String,
        displaySize: String,
    },

    computed : {
        /**
         * Retourn la valeur par default
         * Si currentValue exist alors la valeur sera currentValue sinon on prend la premier valeur du tableau
         */
        selectedValue() {
            return this.currentValue ? this.currentValue : this.lists[0];
        }
    },

    methods : {
        /**
         * Retourne la liste de class a ajouter en fonction des props
         */
        classList() {
            let classList = "";

            if(this.displaySize) {
                classList += ' form-select-'+ this.displaySize;
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
    }
}
</script>