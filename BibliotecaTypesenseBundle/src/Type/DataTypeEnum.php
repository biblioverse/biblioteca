<?php

namespace Biblioteca\TypesenseBundle\Type;

enum DataTypeEnum: string
{
    case PRIMARY = 'primary';
    case STRING = 'string';
    case STRING_ARRAY = 'string[]';
    case INT32 = 'int32';
    case INT32_ARRAY = 'int32[]';
    case INT64 = 'int64';
    case INT64_ARRAY = 'int64[]';
    case FLOAT = 'float';
    case FLOAT_ARRAY = 'float[]';
    case BOOL = 'bool';
    case BOOL_ARRAY = 'bool[]';
    case GEOPOINT = 'geopoint';
    case GEOPOINT_ARRAY = 'geopoint[]';
    case OBJECT = 'object';
    case OBJECT_ARRAY = 'object[]';
    case STRING_CONVERTIBLE = 'string*';
    case IMAGE = 'image';
    case AUTO = 'auto';
}
