## architecture diagrams

 - these are preliminary

## terse note about generating dox
 - you can generate doxygen output for non-marked-up code
 - great for diving into to unknown code 
 - ```doxygen -g config```
 - edit config
``` 
71c71
< CREATE_SUBDIRS         = NO
---
> CREATE_SUBDIRS         = YES
439c439
< EXTRACT_ALL            = NO
---
> EXTRACT_ALL            = YES
445c445
< EXTRACT_PRIVATE        = NO
---
> EXTRACT_PRIVATE        = YES
457c457
< EXTRACT_STATIC         = NO
---
> EXTRACT_STATIC         = YES
868c868
< RECURSIVE              = NO
---
> RECURSIVE              = YES
1471c1471
< GENERATE_TREEVIEW      = NO
---
> GENERATE_TREEVIEW      = YES
2052c2052
< MACRO_EXPANSION        = NO
---
> MACRO_EXPANSION        = YES
2060c2060
< EXPAND_ONLY_PREDEF     = NO
---
> EXPAND_ONLY_PREDEF     = YES
2207c2207
< HAVE_DOT               = NO
---
> HAVE_DOT               = YES
2217c2217
< DOT_NUM_THREADS        = 0
---
> DOT_NUM_THREADS        = 2
2324c2324
< CALL_GRAPH             = NO
---
> CALL_GRAPH             = YES
2336c2336
< CALLER_GRAPH           = NO
---
> CALLER_GRAPH           = YES
2367c2367
< DOT_IMAGE_FORMAT       = png
---
> DOT_IMAGE_FORMAT       = svg
2434c2434
< DOT_GRAPH_MAX_NODES    = 50
---
> DOT_GRAPH_MAX_NODES    = 5000
```
 - copy files into directory
 - ```doxygen config```
