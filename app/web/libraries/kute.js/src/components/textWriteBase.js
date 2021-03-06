import KUTE from '../objects/kute.js'
import numbers from '../interpolation/numbers.js'
import defaultOptions from '../objects/defaultOptions.js'

// Component Values
const lowerCaseAlpha = String("abcdefghijklmnopqrstuvwxyz").split(""), // lowercase
    upperCaseAlpha = String("abcdefghijklmnopqrstuvwxyz").toUpperCase().split(""), // uppercase
    nonAlpha = String("~!@#$%^&*()_+{}[];'<>,./?\=-").split(""), // symbols
    numeric = String("0123456789").split(""), // numeric
    alphaNumeric = lowerCaseAlpha.concat(upperCaseAlpha,numeric), // alpha numeric
    allTypes = alphaNumeric.concat(nonAlpha); // all caracters

const charSet = {
  alpha: lowerCaseAlpha, // lowercase
  upper: upperCaseAlpha, // uppercase
  symbols: nonAlpha, // symbols
  numeric: numeric,
  alphanumeric: alphaNumeric,
  all: allTypes,
}

export {charSet}

// Component Functions
export const onStartWrite = {
  text: function(tweenProp){
    if ( !KUTE[tweenProp] && this.valuesEnd[tweenProp] ) {
      
      let chars = this._textChars,
          charsets = chars in charSet ? charSet[chars] 
                  : chars && chars.length ? chars 
                  : charSet[defaultOptions.textChars]

      KUTE[tweenProp] = function(elem,a,b,v) {
        
        let initialText = '', 
            endText = '',
            firstLetterA = a.substring(0), 
            firstLetterB = b.substring(0),
            pointer = charsets[(Math.random() * charsets.length)>>0];

        if (a === ' ') {
          endText         = firstLetterB.substring(Math.min(v * firstLetterB.length, firstLetterB.length)>>0, 0 );
          elem.innerHTML = v < 1 ? ( ( endText + pointer  ) ) : (b === '' ? ' ' : b);
        } else if (b === ' ') {
          initialText     = firstLetterA.substring(0, Math.min((1-v) * firstLetterA.length, firstLetterA.length)>>0 );
          elem.innerHTML = v < 1 ? ( ( initialText + pointer  ) ) : (b === '' ? ' ' : b);
        } else {
          initialText     = firstLetterA.substring(firstLetterA.length, Math.min(v * firstLetterA.length, firstLetterA.length)>>0 );
          endText         = firstLetterB.substring(0,                   Math.min(v * firstLetterB.length, firstLetterB.length)>>0 );
          elem.innerHTML = v < 1 ? ( (endText + pointer + initialText) ) : (b === '' ? ' ' : b);
        }
      }
    }
  },
  number: function(tweenProp) {
    if ( tweenProp in this.valuesEnd && !KUTE[tweenProp]) { // numbers can be 0
      KUTE[tweenProp] = (elem, a, b, v) => {
        elem.innerHTML = numbers(a, b, v)>>0;
      }
    }
  }
}

// Base Component
export const baseTextWrite = {
  component: 'baseTextWrite',
  category: 'textWrite',
  // properties: ['text','number'],
  // defaultValues: {text: ' ',numbers:'0'},
  defaultOptions: { textChars: 'alpha' },
  Interpolate: {numbers},
  functions: {onStart:onStartWrite},
  // export to global for faster execution
  Util: { charSet }
}

export default baseTextWrite
