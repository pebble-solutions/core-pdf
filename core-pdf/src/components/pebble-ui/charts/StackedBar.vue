<template>
    <div class="progress my-2" :style="styleListe" v-if="!waitData">
        <div v-for="(bar, index) in bars" :key="'bar'+ index" 
            class="progress-bar" :class="'bg-'+bar.color"
            role="progressbar"
            aria-valuemax="100" aria-valuemin="0" :aria-valuenow="bar.value"
            :style="'width:'+bar.percent+'%'">

            <div v-if="bar.displayValue">
                <span v-if="percentage">{{bar.percent}}%</span>
                <span v-else>{{bar.value}}</span>
            </div>
        </div>
    </div>
</template>

<script>
/**
 *  props :
 * - bar {Array} :
 * {
 *      color:          Couleur de boostrap 
 *      value:          Valeur
 * }
 *        
 */
export default {
    props: {
        bars: Array,
        percentage: {
            type: Boolean,
            default: false
        },
        value: {
            type: Boolean,
            default: false
        },
        styleListe: String,
        totalValue: {
            type: Number,
            default: null
        }
    },

    data() {
        return {
            waitData: true,
            Appbars: []
        }
    },

    watch: {
        bars() {
            this.calculPercentage();
        },

        totalValue(newVal, oldVal) {
            if (newVal !== oldVal) {
                this.calculPercentage();
            }
        }
    },

    computed: {
        total() {
            if(this.totalValue) {
                return this.totalValue;
            }

            let total_value = 0;

            this.bars.forEach(bar => {
                total_value += bar.value;
            });

            return total_value;
        }
    },

    methods: {
        calculPercentage() {
            this.Appbars = this.bars;

            this.Appbars.forEach(bar => {
                bar.percent = ((bar.value*100) / this.total).toFixed(0);

                if (typeof bar.displayValue === 'undefined') {
                    bar.displayValue = true;
                }
            });

            this.waitData = false;
        }
    },

    mounted() {
        this.calculPercentage();
    }


}
</script>