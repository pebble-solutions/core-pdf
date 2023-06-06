<template>
    <AppModal 
        id="storage" 
        title="Data viewer" 
        className="modal-dialog-scrollable" 
        :display="display" 
        size="xl"
        
        @modal-hide="$emit('modal-hide')"
        @modal-show="$emit('modal-show')">
        <div class="row">
            <div class="col-3">
                <div class="list-group list-group-flush position-sticky" style="top:0px">
                    <button 
                        @click.prevent="load(key)" 
                        class="list-group-item list-group-item-action" 
                        v-for="(data, key) in datas" 
                        :key="key" 
                        :class="{'active' : activeKey == key}">{{key}}</button>
                </div>
            </div>
            <div class="col overflow-auto" v-if="activeKey">
                <div class="d-flex align-items-center justify-content-between">
                    <h2>{{activeKey}}</h2>
                    <span class="badge bg-info">{{typeof activeData}}</span>
                </div>
                <pre class="border bg-light p-1">{{activeData}}</pre>
            </div>
        </div>
    </AppModal>
</template>

<script>

import AppModal from './AppModal.vue'

export default {
    props: {
        display: {
            type:Boolean,
            default: false
        }
    },

    emits: ['modal-hide', 'modal-show'],

    data() {
        return {
            activeKey: null
        }
    },

    computed: {
        /**
         * Retourne la liste des données chargées par l'application.
         * @returns {Object}
         */
        datas() {
            return this.$store.state;
        },

        /**
         * Retourne les données actives
         * @returns {Object}
         */
        activeData() {
            return this.datas[this.activeKey];
        }
    },

    methods: {
        /**
         * Charge une donnée pour l'affichage.
         */
        load(key) {
            this.activeKey = key;
        }
    },

    components: {
        AppModal
    },

    mounted() {
        let keys = Object.keys(this.datas);

        if (keys) {
            this.activeKey = keys[0];
        }
    }
}
</script>