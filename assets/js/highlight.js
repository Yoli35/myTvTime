import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import twig from 'highlight.js/lib/languages/twig';
// import javascript from 'highlight.js/lib/languages/javascript';

hljs.registerLanguage('php', php);
hljs.registerLanguage('twig', twig);
// hljs.registerLanguage('twig', javascript);

hljs.highlightAll();
