/**
 * Multiple Sticky Elements - Vanilla JavaScript (CSS Sticky based)
 * Accurate implementation using modern CSS position: sticky.
 */

function initMultipleSticky(elements, options = {}) {
    const {
        offset = 0,
        minContainerWidth = 768,
        container = null,
        gap = 0,
        zIndex = 1000
    } = options;

    let elementsArray = [];
    let containerElement = null;
    let isStickyActive = false;

    function getElements() {
        if (typeof elements === 'string') {
            return Array.from(document.querySelectorAll(elements));
        }
        if (elements instanceof NodeList || elements instanceof HTMLCollection) {
            return Array.from(elements);
        }
        return Array.isArray(elements) ? elements : [elements];
    }

    function resetStyles(els) {
        els.forEach(el => {
            if (el && el.style) {
                el.style.position = '';
                el.style.top = '';
                el.style.zIndex = '';
                el.style.marginTop = '';
                el.style.marginBottom = '';
            }
        });
    }

    // MutationObserver to handle dynamic content changes
    const observer = new MutationObserver(() => {
        if (isStickyActive) {
            applyStickyStyles();
        }
    });

    function applyStickyStyles() {
        const currentContainerWidth = containerElement ? containerElement.clientWidth : window.innerWidth;
        const shouldBeSticky = currentContainerWidth >= minContainerWidth;

        if (shouldBeSticky) {
            // Calculate heights and cumulative space needed below each element
            const heights = elementsArray.map(el => el ? el.offsetHeight : 0);
            const cumulativeBottomSpace = new Array(elementsArray.length).fill(0);
            
            for (let i = elementsArray.length - 2; i >= 0; i--) {
                cumulativeBottomSpace[i] = cumulativeBottomSpace[i + 1] + heights[i + 1] + gap;
            }

            // Temporarily disconnect observer to prevent infinite loop during style changes
            observer.disconnect();

            let currentTop = offset;
            elementsArray.forEach((el, index) => {
                if (el) {
                    el.style.position = 'sticky';
                    el.style.top = `${currentTop}px`;
                    el.style.zIndex = (zIndex + index).toString();
                    
                    // The margin-bottom trick ensures that the sticky element's "container constraint"
                    // includes space for all the sticky elements below it.
                    el.style.marginBottom = `${cumulativeBottomSpace[index]}px`;
                    
                    // To prevent this large margin-bottom from actually pushing the next elements 
                    // down in the normal layout, we apply a corresponding negative margin-top 
                    // to the current element (except the first one).
                    if (index > 0) {
                        el.style.marginTop = `-${cumulativeBottomSpace[index - 1] - gap}px`;
                    }
                    
                    currentTop += heights[index] + gap;
                }
            });

            // Reconnect observer
            elementsArray.forEach(el => {
                if (el) observer.observe(el, { attributes: true, childList: true, subtree: true });
            });

            isStickyActive = true;
        } else {
            if (isStickyActive) {
                resetStyles(elementsArray);
                isStickyActive = false;
            }
        }
    }

    function initialize() {
        elementsArray = getElements().filter(el => el !== null);
        containerElement = typeof container === 'string' ? document.querySelector(container) : container;

        if (elementsArray.length > 0) {
            // First reset to get natural offsets
            resetStyles(elementsArray);
            applyStickyStyles();
        }
    }

    // Setup listeners
    if (document.readyState === 'complete') {
        initialize();
    } else {
        window.addEventListener('load', initialize);
    }

    const handleResize = () => {
        applyStickyStyles();
    };

    window.addEventListener('resize', handleResize);

    // Return control functions
    return {
        destroy: () => {
            window.removeEventListener('load', initialize);
            window.removeEventListener('resize', handleResize);
            observer.disconnect();
            resetStyles(elementsArray);
        },
        refresh: () => {
            initialize();
        }
    };
}

// Export for different environments
if (typeof module !== 'undefined' && module.exports) {
    module.exports = initMultipleSticky;
} else if (typeof define === 'function' && define.amd) {
    define([], function() { return initMultipleSticky; });
} else {
    window.initMultipleSticky = initMultipleSticky;
}
