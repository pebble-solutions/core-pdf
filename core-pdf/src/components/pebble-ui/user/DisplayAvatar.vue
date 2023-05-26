<template>
    <UserImage :name="imageName" size="user-image-xl" @click.prevent="cliquable()"/>
    
    <div class="input-group mb-3 mt-3" v-if="imgUser && !lock">
        <input type="file" class="form-control" id="inputGroupFile02">
        <label class="input-group-text" for="inputGroupFile02">Importer</label>
    </div> 

</template>


<script>

import UserImage from '../UserImage.vue';

export default {
    props: {
        userName:String,
        lock:Boolean,
    },
    data() {
        return {
            imgUser:false,
        }
    },
    emits:["edit-mode"],
    watch:{

        /**
         * Retourne à l'élement parent un Object explicitant sur l'état des displays afin de bloquer ou non les autres 
         * @param {Boolean} newVal 
         */
        imgUser(newVal){
            this.$emit('edit-mode',newVal);
        }
    },
    methods:{

        /**
         * Action lors d'au click à l'ouverture. En fonction de la variable de bloquage des display, 
         * il modifie la valeur booleenne d'affichage du display Avatar 
         */
        cliquable(){
            if(!this.lock){
                this.imgUser = !this.imgUser
            }
        }
    },
    computed: {

        /**
         * Determine et retourne le pseudo pris en compte pour l'image
         * 
         * @returns {string}
         */
        imageName(){
            if(this.userName === 'Pseudo Non Défini') return "?" ;
            else return this.userName;
        },

    },
    components: { UserImage },

}

</script>