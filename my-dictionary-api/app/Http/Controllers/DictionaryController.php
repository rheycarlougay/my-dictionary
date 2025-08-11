<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DictionaryController extends Controller
{
    public function search(Request $request) {
        $word = $request->q;   
        
        try {
            $results = Http::timeout(30)
                ->withOptions([
                    'verify' => false, 
                    'timeout' => 30,
                ])
                ->get("https://api.dictionaryapi.dev/api/v2/entries/en/$word");

            $results = $results->json();

            $data = [];

            if (isset($results['title'])) {
                return response()->json(['status_code' => 404, 'message' => 'No definitions found', 'data' => $data]);
            } else {
                $temp_meanings = [];

                $data[0]['word'] = $results[0]['word'];
                $data[0]['phonetics'] = [];
                $data[0]['partOfSpeech'] = [];
                $data[0]['definitions'] = [];
                $data[0]['examples'] = [];
                $data[0]['synonyms'] = [];

                foreach($results as $resultIndex => $result) {
                    $data[0]['phonetics'] = $this->decodePhonetics($result, $data[0]['phonetics']);
                    
                    $meanings = $this->decodeMeanings($result, $data[0]['partOfSpeech']);
                    $data[0]['partOfSpeech'] = $meanings['speeches'];
                    $temp_meanings[] = $meanings;
                }

                $data[0]['definitions'] = $this->mergeSameSpeech($temp_meanings, 'definitions');
                $data[0]['examples'] = $this->mergeSameSpeech($temp_meanings, 'examples');
                $data[0]['synonyms'] = $this->mergeSameSpeech($temp_meanings, 'synonyms');
                
                return response()->json(['status_code' => 200, 'message' => 'Definitions found', 'data' => $data]);
            }
        } catch (\Exception $e) {
            Log::error('Dictionary API request failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch dictionary data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function decodePhonetics($result, $existing) {
        $phonetics = $existing;

        // cleanup duplicates and objects that don't have text and audio
        foreach($result['phonetics'] as $phonetic) {
            if(!empty($phonetic['text']) && !empty($phonetic['audio']) && !in_array($phonetic['text'], $phonetics)) {
                $phonetics[] = $phonetic['text'];
            }
        }

        return $phonetics;
    }

    public function decodeMeanings($result, $existing) {
        $return['speeches'] = $existing;
        $return['definitions'] = [];
        $return['examples'] = [];
        $return['synonyms'] = [];

        // cleanup duplicates and objects that don't have definitions, part of speech, example, and synonyms
        foreach($result['meanings'] as $meaning) {
            if(!empty($meaning['partOfSpeech']) && !empty($meaning['definitions']) && !in_array($meaning['partOfSpeech'], $return['speeches'])) {
                $return['speeches'][] = $meaning['partOfSpeech'];
            }

            foreach($meaning['definitions'] as $definition) {
                $return['definitions'][$meaning['partOfSpeech']][] = $definition['definition'];
                
                if(!empty($definition['example'])) {
                    $return['examples'][$meaning['partOfSpeech']][] = $definition['example'];
                }

                if(!empty($definition['synonyms'])) {
                    foreach($definition['synonyms'] as $synonym) {
                        $return['synonyms'][$meaning['partOfSpeech']][] = $synonym;
                    }
                }
            }
        }
        
        return $return;
    }

    public function mergeSameSpeech(array $data, $item_key): array {
        $result = [];

        foreach ($data as $item) {
            foreach ($item[$item_key] as $key => $definitions) {
                if (!isset($result[$key])) {
                    $result[$key] = [];
                }
                $result[$key] = array_merge($result[$key], $definitions);
            }
        }
    
        return array_values($result);
    }
}