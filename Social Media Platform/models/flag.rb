class Flag
  include Neo4j::ActiveRel
  include Neo4j::Timestamps

  WRONG_TAG   = 1
  SPAM        = 2
  EXPLICIT    = 3
  ABUSIVE     = 4

  creates_unique

  from_class :User
  to_class :Post
  type :flagged

  property :flag, type: Integer
  validates :flag, numericality: {greater_than_or_equal_to: WRONG_TAG, less_than_or_equal_to: ABUSIVE}
end
