<template>
    <div v-if="display == 'full'" class="my-2 text-center">
        <span class="user-image" :style="'background-image:url('+imageUrl+');'" v-if="imageUrl" :class="classList"></span>
        <span class="user-image" :style="'background-color:'+finalName.toColor()+';'" v-else :class="classList">
            {{finalName[0].toUpperCase()}}
        </span>
        <div class="mt-2" :class="classNameUsername">{{finalName}}</div>
    </div>
    <span class="user-image" :style="'background-image:url('+imageUrl+');'" v-else-if="imageUrl" :class="classList"></span>
    <span class="user-image" :style="'background-color:'+finalName.toColor()+';'" v-else :class="classList">
        {{finalName[0].toUpperCase()}}
    </span>
</template>

<style lang="scss" scoped>
.user-image {
    background-size:cover;
    background-position:center; 
    border-radius:50%;
    text-decoration: none;
    color:white;
    width:32px; height:32px;
    line-height: 32px; 
    min-width: 32px; min-height: 32px;
    display: block;
    text-align: center;
    margin: auto;
}

.user-image-xs {
    width:12px; height:12px;
    line-height: 12px; 
    min-width: 12px; min-height: 12px;
}
.user-image-sm {
    width:24px; height:24px;
    line-height: 24px; 
    min-width: 24px; min-height: 24px;
}
.user-image-lg {
    width:48px; height:48px;
    line-height: 48px; 
    min-width: 48px; min-height: 48px;
}

.user-image-xl {
    width:96px; height:96px;
    line-height: 96px; 
    min-width: 96px; min-height: 96px;
    font-size: 32px;
}
</style>

<script>

import stc from 'string-to-color'

String.prototype.toColor = function() {
    if (this === '?') return '#aaaaaa';
    else return stc(this);
};

export default {
    props: {
        size: String,
        className: String,
        name: String,
        imageUrl: String,
        display: {
            type: String,
            default: 'image'
        },
        classNameUsername: {
            type: String,
            default: ''
        }
    },

    computed: {
        /** 
         * Retourne la liste de class a ajouter
         * size posible: 'xs','sm','lg', 'xl' 
         * 
         * @return String
         */
        classList() {
            let classList = '';

            if(this.size) {
                if(this.size.search(/user-image-/) != -1) {
                    classList += this.size
                } else {
                    classList += 'user-image-' + this.size;
                }
            }

            if(this.className) {
                classList += ' ' + this.className;
            }

            return classList;
        },


        /**
         * Transforme la data name en une chaine de caractère obligatoire. Si name est null
         * ou undefined alors on retourne la chaine '?'
         * 
         * @returns {String}
         */
        finalName() {

            return typeof this.name === 'string' ? this.name : '?';
        }
    }
}
</script>